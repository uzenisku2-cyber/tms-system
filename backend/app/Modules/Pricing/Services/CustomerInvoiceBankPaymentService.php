<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Fleet\Services\BankTransactionEvidenceCapacityService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\CustomerInvoiceBankPayment;
use App\Modules\Pricing\Models\CustomerInvoiceBankPaymentEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CustomerInvoiceBankPaymentService
{
    public function __construct(
        private readonly OrganizationContext $organizations,
        private readonly CustomerInvoiceDraftService $invoices,
        private readonly BankTransactionEvidenceCapacityService $capacity,
    ) {}

    /** @return array<string, mixed> */
    public function index(string $invoicePublicId): array
    {
        $document = $this->issuerDocument($invoicePublicId);
        $total = $this->capacity->minor((string) $document->gross_amount);
        $paid = $this->paidMinor($document);
        $payments = CustomerInvoiceBankPayment::query()->where('billing_document_id', $document->id)
            ->orderBy('id')->get();

        return [
            'invoice_amount_minor' => $total, 'paid_amount_minor' => $paid,
            'unpaid_amount_minor' => max(0, $total - $paid),
            'payment_state' => $paid <= 0 ? 'unpaid' : ($paid < $total ? 'partially_paid' : 'paid'),
            'currency' => $document->currency,
            'payments' => $payments->map(fn (CustomerInvoiceBankPayment $payment): array => $this->present($payment))->all(),
        ];
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function allocate(string $invoicePublicId, array $data, User $actor): array
    {
        $this->issuerDocument($invoicePublicId);
        $ownerId = $this->organizations->requireId();
        $fingerprint = hash('sha256', json_encode([
            $invoicePublicId, $data['bank_transaction_evidence_public_id'],
            (int) $data['expected_bank_revision'], (int) $data['allocated_amount_minor'], $data['reason'],
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($invoicePublicId, $data, $actor, $ownerId, $fingerprint): array {
            $existing = CustomerInvoiceBankPayment::query()->where('owner_organization_id', $ownerId)
                ->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing instanceof CustomerInvoiceBankPayment) {
                if (! hash_equals($existing->command_fingerprint, $fingerprint)) {
                    $this->invalid('idempotency_key', 'This key was used for another allocation.');
                }

                return $this->present($existing);
            }
            $document = BillingDocument::query()->where('public_id', $invoicePublicId)
                ->where('owner_organization_id', $ownerId)
                ->where('document_type', BillingDocument::TYPE_CUSTOMER_INVOICE)
                ->lockForUpdate()->firstOrFail();
            abort_unless(in_array($document->status, ['approved', 'closed'], true), 409);
            $identity = $document->commercialIdentity;
            abort_unless($identity !== null && $identity->direction === 'receivable', 409);
            $snapshot = $identity->counterparty_snapshot;
            $paymentIban = is_array($snapshot) ? ($snapshot['payment_iban'] ?? null) : null;
            if (! is_string($paymentIban) || $paymentIban === '' || ! is_string($identity->variable_symbol)
                || $identity->variable_symbol === '') {
                $this->invalid('bank_transaction_evidence_public_id', 'The issued invoice needs payment account and variable symbol.');
            }
            $evidence = BankTransactionEvidence::query()
                ->where('public_id', $data['bank_transaction_evidence_public_id'])
                ->where('organization_context_id', $ownerId)
                ->lockForUpdate()->firstOrFail();
            if ($evidence->status !== 'recorded' || (int) $evidence->revision !== (int) $data['expected_bank_revision']) {
                $this->invalid('expected_bank_revision', 'The bank evidence is stale or unavailable.');
            }
            if ($evidence->direction !== 'credit' || $evidence->currency !== $document->currency
                || $evidence->variable_symbol !== $identity->variable_symbol
                || $this->account((string) $evidence->account_identifier) !== $this->account($paymentIban)) {
                $this->invalid('bank_transaction_evidence_public_id', 'Bank direction, currency, symbol or receiving account does not match the invoice.');
            }
            $amount = (int) $data['allocated_amount_minor'];
            $unpaid = $this->capacity->minor((string) $document->gross_amount) - $this->paidMinor($document);
            if ($amount > $unpaid || $amount > $this->capacity->remainingMinor($evidence)) {
                $this->invalid('allocated_amount_minor', 'Amount exceeds the unpaid invoice or available bank evidence.');
            }
            $payment = CustomerInvoiceBankPayment::query()->create([
                'owner_organization_id' => $ownerId, 'billing_document_id' => $document->id,
                'bank_transaction_evidence_id' => $evidence->id,
                'bank_transaction_evidence_revision' => (int) $evidence->revision,
                'idempotency_key' => $data['idempotency_key'], 'command_fingerprint' => $fingerprint,
                'allocated_amount_minor' => $amount, 'currency' => $document->currency,
                'status' => 'active', 'reason' => $data['reason'],
                'allocated_by_user_id' => $actor->id, 'allocated_at' => now(), 'revision' => 1,
            ]);
            $this->event($payment, 1, 'allocated', $data['idempotency_key'], $fingerprint, $data['reason'], $actor,
                ['invoice_public_id' => $invoicePublicId, 'bank_evidence_public_id' => $evidence->public_id,
                    'allocated_amount_minor' => $amount, 'invoice_unpaid_before_minor' => $unpaid,
                    'bank_remaining_before_minor' => $this->capacity->minor((string) $evidence->amount)
                        - $this->capacity->allocatedMinor($evidence) + $amount]);

            return $this->present($payment);
        });
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function reverse(string $invoicePublicId, string $paymentPublicId, array $data, User $actor): array
    {
        $this->issuerDocument($invoicePublicId);
        $ownerId = $this->organizations->requireId();
        $fingerprint = hash('sha256', json_encode([$invoicePublicId, $paymentPublicId, $data['expected_revision'], $data['reason']], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($invoicePublicId, $paymentPublicId, $data, $actor, $ownerId, $fingerprint): array {
            $document = BillingDocument::query()->where('public_id', $invoicePublicId)
                ->where('owner_organization_id', $ownerId)
                ->where('document_type', BillingDocument::TYPE_CUSTOMER_INVOICE)
                ->lockForUpdate()->firstOrFail();
            $payment = CustomerInvoiceBankPayment::query()->where('public_id', $paymentPublicId)
                ->where('billing_document_id', $document->id)->where('owner_organization_id', $ownerId)
                ->lockForUpdate()->firstOrFail();
            $existing = CustomerInvoiceBankPaymentEvent::query()->where('customer_invoice_bank_payment_id', $payment->id)
                ->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing instanceof CustomerInvoiceBankPaymentEvent) {
                if (! hash_equals($existing->command_fingerprint, $fingerprint)) {
                    $this->invalid('idempotency_key', 'This key was used for another reversal.');
                }

                return $this->present($payment);
            }
            abort_unless($payment->status === 'active' && (int) $payment->revision === (int) $data['expected_revision'], 409);
            BankTransactionEvidence::query()->whereKey($payment->bank_transaction_evidence_id)->lockForUpdate()->firstOrFail();
            $payment->forceFill([
                'status' => 'reversed', 'reversed_by_user_id' => $actor->id,
                'reversed_at' => now(), 'reversal_reason' => $data['reason'], 'revision' => 2,
            ])->save();
            $this->event($payment, 2, 'reversed', $data['idempotency_key'], $fingerprint, $data['reason'], $actor,
                ['invoice_public_id' => $invoicePublicId,
                    'released_amount_minor' => (int) $payment->allocated_amount_minor]);

            return $this->present($payment);
        });
    }

    private function issuerDocument(string $publicId): BillingDocument
    {
        $this->invoices->show($publicId);
        $ownerId = $this->organizations->requireId();
        abort_unless(Organization::query()->whereKey($ownerId)->value('type') === Organization::TYPE_MASTER, 403);

        return BillingDocument::query()->where('public_id', $publicId)
            ->where('document_type', BillingDocument::TYPE_CUSTOMER_INVOICE)
            ->where('owner_organization_id', $ownerId)->firstOrFail();
    }

    private function paidMinor(BillingDocument $document): int
    {
        return (int) CustomerInvoiceBankPayment::query()->where('billing_document_id', $document->id)
            ->where('status', 'active')->sum('allocated_amount_minor');
    }

    private function account(string $value): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', $value));
    }

    /** @param array<string, mixed> $evidence */
    private function event(CustomerInvoiceBankPayment $payment, int $revision, string $type, string $key,
        string $fingerprint, string $reason, User $actor, array $evidence): void
    {
        CustomerInvoiceBankPaymentEvent::query()->create([
            'customer_invoice_bank_payment_id' => $payment->id, 'revision' => $revision,
            'event_type' => $type, 'idempotency_key' => $key, 'command_fingerprint' => $fingerprint,
            'reason' => $reason, 'evidence' => $evidence, 'actor_user_id' => $actor->id,
            'occurred_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function present(CustomerInvoiceBankPayment $payment): array
    {
        $bank = BankTransactionEvidence::query()->find($payment->bank_transaction_evidence_id);

        return [
            'public_id' => $payment->public_id, 'status' => $payment->status,
            'revision' => $payment->revision, 'allocated_amount_minor' => $payment->allocated_amount_minor,
            'currency' => $payment->currency, 'bank_transaction_evidence_public_id' => $bank?->public_id,
            'bank_transaction_evidence_revision' => $payment->bank_transaction_evidence_revision,
            'reason' => $payment->reason, 'allocated_at' => $payment->getRawOriginal('allocated_at'),
            'reversal_reason' => $payment->reversal_reason, 'reversed_at' => $payment->getRawOriginal('reversed_at'),
        ];
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
