<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentity;
use App\Modules\Pricing\Models\SupplierFuelInvoiceBankPayment;
use App\Modules\Pricing\Models\SupplierFuelInvoiceBankPaymentEvent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SupplierFuelInvoiceBankPaymentService
{
    /** @param array<string, mixed> $data @return array{data: array<string, mixed>, replayed: bool} */
    public function store(string $invoicePublicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('compensation.manage'), 403);
        $amountMinor = $this->minor((string) $data['allocated_amount']);
        $fingerprint = hash('sha256', json_encode([
            'invoice' => $invoicePublicId,
            'organization_id' => $organizationId,
            'data' => Arr::sortRecursive($data),
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($invoicePublicId, $data, $organizationId, $actor, $amountMinor, $fingerprint): array {
            $key = (string) $data['idempotency_key'];
            $existing = SupplierFuelInvoiceBankPayment::query()
                ->where('owner_organization_id', $organizationId)
                ->where('idempotency_key', $key)
                ->first();
            if ($existing instanceof SupplierFuelInvoiceBankPayment) {
                if (! hash_equals((string) $existing->command_fingerprint, $fingerprint)) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key was already used with a different command.']]);
                }

                return ['data' => $this->present($existing), 'replayed' => true];
            }

            [$identity, $document] = $this->lockInvoice($invoicePublicId, $organizationId);
            $evidence = BankTransactionEvidence::query()
                ->where('public_id', (string) $data['bank_transaction_evidence_public_id'])
                ->where('organization_context_id', $organizationId)
                ->lockForUpdate()
                ->firstOrFail();
            if ((int) $evidence->revision !== (int) $data['expected_bank_transaction_evidence_revision']) {
                throw ValidationException::withMessages(['expected_bank_transaction_evidence_revision' => ['The bank transaction evidence revision is stale.']]);
            }
            if ($evidence->status !== 'recorded' || $evidence->direction !== 'debit') {
                throw ValidationException::withMessages(['bank_transaction_evidence_public_id' => ['Only recorded debit bank transaction evidence may pay a supplier invoice.']]);
            }
            if ($evidence->currency !== $document->currency) {
                throw ValidationException::withMessages(['bank_transaction_evidence_public_id' => ['Bank transaction currency does not match the supplier invoice.']]);
            }

            $invoiceMinor = $this->minor((string) $document->gross_amount);
            $invoiceAllocated = (int) SupplierFuelInvoiceBankPayment::query()
                ->where('billing_document_id', $document->id)
                ->where('status', SupplierFuelInvoiceBankPayment::STATUS_ACTIVE)
                ->sum('allocated_amount_minor');
            $evidenceMinor = $this->minor((string) $evidence->amount);
            $evidenceAllocated = (int) SupplierFuelInvoiceBankPayment::query()
                ->where('bank_transaction_evidence_id', $evidence->id)
                ->where('status', SupplierFuelInvoiceBankPayment::STATUS_ACTIVE)
                ->sum('allocated_amount_minor');
            if ($amountMinor <= 0 || $amountMinor > $invoiceMinor - $invoiceAllocated) {
                throw ValidationException::withMessages(['allocated_amount' => ['Allocated amount exceeds the unpaid supplier invoice amount.']]);
            }
            if ($amountMinor > $evidenceMinor - $evidenceAllocated) {
                throw ValidationException::withMessages(['allocated_amount' => ['Allocated amount exceeds the unallocated bank transaction amount.']]);
            }

            $payment = SupplierFuelInvoiceBankPayment::query()->create([
                'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId,
                'billing_document_id' => $document->id, 'bank_transaction_evidence_id' => $evidence->id,
                'bank_transaction_evidence_revision' => (int) $evidence->revision,
                'idempotency_key' => $key, 'command_fingerprint' => $fingerprint,
                'allocated_amount_minor' => $amountMinor, 'currency' => $document->currency,
                'status' => SupplierFuelInvoiceBankPayment::STATUS_ACTIVE, 'reason' => trim((string) $data['reason']),
                'matched_by_user_id' => $actor->id, 'matched_at' => now(), 'revision' => 1,
            ]);
            SupplierFuelInvoiceBankPaymentEvent::query()->create([
                'public_id' => (string) Str::uuid(), 'supplier_fuel_invoice_bank_payment_id' => $payment->id,
                'revision' => 1, 'event_type' => 'payment_allocated', 'idempotency_key' => $key,
                'command_fingerprint' => $fingerprint, 'reason' => $payment->reason,
                'evidence' => [
                    'supplier_fuel_invoice_public_id' => $identity->public_id,
                    'bank_transaction_evidence_public_id' => $evidence->public_id,
                    'allocated_amount_minor' => $amountMinor, 'currency' => $document->currency,
                    'bank_matching_performed' => true, 'fuel_settlement_mutated' => false,
                ],
                'actor_user_id' => $actor->id, 'occurred_at' => now(),
            ]);

            return ['data' => $this->present($payment), 'replayed' => false];
        });
    }

    /** @param array<string, mixed> $data @return array{data: array<string, mixed>, replayed: bool} */
    public function reverse(string $invoicePublicId, string $paymentPublicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('compensation.manage'), 403);
        $fingerprint = hash('sha256', json_encode([
            'invoice' => $invoicePublicId, 'payment' => $paymentPublicId,
            'organization_id' => $organizationId, 'data' => Arr::sortRecursive($data),
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($invoicePublicId, $paymentPublicId, $data, $organizationId, $actor, $fingerprint): array {
            [, $document] = $this->lockInvoice($invoicePublicId, $organizationId);
            $payment = SupplierFuelInvoiceBankPayment::query()
                ->where('public_id', $paymentPublicId)->where('owner_organization_id', $organizationId)
                ->where('billing_document_id', $document->id)->lockForUpdate()->firstOrFail();
            $key = (string) $data['idempotency_key'];
            $event = SupplierFuelInvoiceBankPaymentEvent::query()
                ->where('supplier_fuel_invoice_bank_payment_id', $payment->id)
                ->where('idempotency_key', $key)->first();
            if ($event instanceof SupplierFuelInvoiceBankPaymentEvent) {
                if (! hash_equals((string) $event->command_fingerprint, $fingerprint)) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key was already used with a different command.']]);
                }

                return ['data' => $this->present($payment), 'replayed' => true];
            }
            if ($payment->status !== SupplierFuelInvoiceBankPayment::STATUS_ACTIVE) {
                throw ValidationException::withMessages(['payment' => ['Only an active payment allocation may be reversed.']]);
            }
            if ((int) $payment->revision !== (int) $data['expected_revision']) {
                throw ValidationException::withMessages(['expected_revision' => ['The payment allocation revision is stale.']]);
            }
            $reason = trim((string) $data['reason']);
            $payment->forceFill([
                'status' => SupplierFuelInvoiceBankPayment::STATUS_REVERSED,
                'reversed_by_user_id' => $actor->id, 'reversed_at' => now(),
                'reversal_reason' => $reason, 'revision' => 2,
            ])->save();
            SupplierFuelInvoiceBankPaymentEvent::query()->create([
                'public_id' => (string) Str::uuid(), 'supplier_fuel_invoice_bank_payment_id' => $payment->id,
                'revision' => 2, 'event_type' => 'payment_reversed', 'idempotency_key' => $key,
                'command_fingerprint' => $fingerprint, 'reason' => $reason,
                'evidence' => ['released_amount_minor' => (int) $payment->allocated_amount_minor, 'bank_matching_performed' => false, 'fuel_settlement_mutated' => false],
                'actor_user_id' => $actor->id, 'occurred_at' => now(),
            ]);

            return ['data' => $this->present($payment->fresh()), 'replayed' => false];
        });
    }

    /** @return array{0: BillingDocumentCommercialIdentity, 1: BillingDocument} */
    private function lockInvoice(string $publicId, int $organizationId): array
    {
        $identity = BillingDocumentCommercialIdentity::query()
            ->where('public_id', $publicId)->where('owner_organization_id', $organizationId)
            ->lockForUpdate()->firstOrFail();
        $document = BillingDocument::query()->whereKey($identity->billing_document_id)->lockForUpdate()->firstOrFail();
        if ($document->document_type !== BillingDocument::TYPE_SUPPLIER_FUEL_INVOICE || $identity->direction !== BillingDocumentCommercialIdentity::DIRECTION_PAYABLE) {
            throw ValidationException::withMessages(['supplier_fuel_invoice' => ['The selected document is not a payable supplier fuel invoice.']]);
        }

        return [$identity, $document];
    }

    private function minor(string $amount): int
    {
        if (preg_match('/^(\d+)\.(\d{2})$/', $amount, $parts) !== 1) {
            throw new \LogicException('Payment amount is not an exact two-decimal value.');
        }

        return ((int) $parts[1] * 100) + (int) $parts[2];
    }

    /** @return array<string, mixed> */
    private function present(SupplierFuelInvoiceBankPayment $payment): array
    {
        $payment->loadMissing(['billingDocument.commercialIdentity', 'bankTransactionEvidence', 'events']);
        $document = $payment->billingDocument;
        $evidence = $payment->bankTransactionEvidence;
        if (! $document instanceof BillingDocument || ! $evidence instanceof BankTransactionEvidence) {
            throw new \LogicException('Payment allocation relations are incomplete.');
        }
        $invoiceMinor = $this->minor((string) $document->gross_amount);
        $paidMinor = (int) SupplierFuelInvoiceBankPayment::query()->where('billing_document_id', $document->id)
            ->where('status', SupplierFuelInvoiceBankPayment::STATUS_ACTIVE)->sum('allocated_amount_minor');
        $evidenceMinor = $this->minor((string) $evidence->amount);
        $evidenceAllocated = (int) SupplierFuelInvoiceBankPayment::query()->where('bank_transaction_evidence_id', $evidence->id)
            ->where('status', SupplierFuelInvoiceBankPayment::STATUS_ACTIVE)->sum('allocated_amount_minor');
        $state = $paidMinor === 0 ? 'unpaid' : ($paidMinor === $invoiceMinor ? 'paid' : 'partially_paid');

        return [
            'public_id' => $payment->public_id,
            'supplier_fuel_invoice_public_id' => $document->commercialIdentity?->public_id,
            'bank_transaction_evidence_public_id' => $evidence->public_id,
            'allocated_amount_minor' => (int) $payment->allocated_amount_minor,
            'currency' => $payment->currency, 'status' => $payment->status,
            'revision' => (int) $payment->revision, 'invoice_payment_state' => $state,
            'invoice_paid_amount_minor' => $paidMinor,
            'invoice_unpaid_amount_minor' => $invoiceMinor - $paidMinor,
            'bank_transaction_allocated_amount_minor' => $evidenceAllocated,
            'bank_transaction_unallocated_amount_minor' => $evidenceMinor - $evidenceAllocated,
            'events' => $payment->events->toArray(),
            'bank_matching_performed' => $payment->status === SupplierFuelInvoiceBankPayment::STATUS_ACTIVE,
            'payment_marked' => $state === 'paid', 'fuel_settlement_mutated' => false,
        ];
    }
}
