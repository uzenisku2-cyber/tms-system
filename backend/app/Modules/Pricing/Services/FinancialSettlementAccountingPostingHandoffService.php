<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingHandoff;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingHandoffEvent;
use App\Modules\Pricing\Models\FinancialSettlementBankPayment;
use App\Modules\Pricing\Models\FinancialSettlementBankPaymentReconciliation;
use App\Modules\Pricing\Models\FinancialSettlementStatement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FinancialSettlementAccountingPostingHandoffService
{
    /** @param array<string, mixed> $data @return array{data: array<string, mixed>, replayed: bool} */
    public function prepare(
        string $statementPublicId,
        string $paymentPublicId,
        array $data,
        int $organizationId,
        User $actor,
    ): array {
        abort_unless($actor->can('compensation.manage'), 403);
        $fingerprint = $this->fingerprint($statementPublicId, $paymentPublicId, $organizationId, $data);

        return DB::transaction(function () use ($statementPublicId, $paymentPublicId, $data, $organizationId, $actor, $fingerprint): array {
            $replay = FinancialSettlementAccountingPostingHandoff::query()
                ->where('owner_organization_id', $organizationId)
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();
            if ($replay instanceof FinancialSettlementAccountingPostingHandoff) {
                if (! hash_equals((string) $replay->command_fingerprint, $fingerprint)) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key was already used with a different command.']]);
                }

                return ['data' => $this->present($replay), 'replayed' => true];
            }

            $payment = FinancialSettlementBankPayment::query()
                ->where('public_id', $paymentPublicId)
                ->where('owner_organization_id', $organizationId)
                ->whereHas('statement', static fn (Builder $query): Builder => $query->where('public_id', $statementPublicId)->where('owner_organization_id', $organizationId))
                ->lockForUpdate()
                ->firstOrFail();
            if ($payment->status !== FinancialSettlementBankPayment::STATUS_ACTIVE) {
                throw ValidationException::withMessages(['payment' => ['Only an active settlement bank payment may prepare an accounting posting handoff.']]);
            }
            if ((int) $payment->revision !== (int) $data['expected_payment_revision']) {
                throw ValidationException::withMessages(['expected_payment_revision' => ['The settlement bank payment revision is stale.']]);
            }

            $reconciliation = FinancialSettlementBankPaymentReconciliation::query()
                ->where('owner_organization_id', $organizationId)
                ->where('financial_settlement_bank_payment_id', $payment->id)
                ->lockForUpdate()
                ->firstOrFail();
            if ($reconciliation->status !== FinancialSettlementBankPaymentReconciliation::STATUS_CONFIRMED) {
                throw ValidationException::withMessages(['reconciliation' => ['Only a confirmed reconciliation may prepare an accounting posting handoff.']]);
            }
            if ((int) $reconciliation->revision !== (int) $data['expected_reconciliation_revision']) {
                throw ValidationException::withMessages(['expected_reconciliation_revision' => ['The settlement bank payment reconciliation revision is stale.']]);
            }

            $existing = FinancialSettlementAccountingPostingHandoff::query()
                ->where('financial_settlement_bank_payment_reconciliation_id', $reconciliation->id)
                ->where('reconciliation_revision', $reconciliation->revision)
                ->first();
            if ($existing instanceof FinancialSettlementAccountingPostingHandoff) {
                throw ValidationException::withMessages(['reconciliation' => ['This reconciliation revision already has an accounting posting handoff.']]);
            }

            $statement = FinancialSettlementStatement::query()
                ->whereKey($payment->financial_settlement_statement_id)
                ->where('owner_organization_id', $organizationId)
                ->firstOrFail();
            $evidence = BankTransactionEvidence::query()
                ->whereKey($payment->bank_transaction_evidence_id)
                ->where('organization_context_id', $organizationId)
                ->firstOrFail();
            $document = $payment->billing_document_id === null
                ? null
                : BillingDocument::query()->whereKey($payment->billing_document_id)->where('owner_organization_id', $organizationId)->firstOrFail();

            $handoff = FinancialSettlementAccountingPostingHandoff::query()->create([
                'public_id' => (string) Str::uuid(),
                'owner_organization_id' => $organizationId,
                'financial_settlement_bank_payment_reconciliation_id' => $reconciliation->id,
                'financial_settlement_bank_payment_id' => $payment->id,
                'financial_settlement_statement_id' => $statement->id,
                'billing_document_id' => $document?->id,
                'bank_transaction_evidence_id' => $evidence->id,
                'idempotency_key' => $data['idempotency_key'],
                'command_fingerprint' => $fingerprint,
                'reconciliation_revision' => $reconciliation->revision,
                'payment_revision' => $payment->revision,
                'statement_revision' => $statement->revision,
                'bank_transaction_evidence_revision' => $evidence->revision,
                'posting_date' => $data['posting_date'],
                'accounting_reference' => $data['accounting_reference'] ?? null,
                'amount_minor' => $payment->allocated_amount_minor,
                'currency' => $payment->currency,
                'direction' => $statement->output_direction,
                'status' => FinancialSettlementAccountingPostingHandoff::STATUS_PREPARED,
                'reason' => $data['reason'],
                'source_snapshot' => [
                    'statement_public_id' => $statement->public_id,
                    'payment_public_id' => $payment->public_id,
                    'reconciliation_public_id' => $reconciliation->public_id,
                    'billing_document_public_id' => $document?->public_id,
                    'bank_transaction_evidence_public_id' => $evidence->public_id,
                    'bank_source_reference' => $evidence->source_reference,
                    'bank_booked_at' => $evidence->getRawOriginal('booked_at'),
                    'amount_minor' => (int) $payment->allocated_amount_minor,
                    'currency' => $payment->currency,
                    'direction' => $statement->output_direction,
                ],
                'prepared_by_user_id' => $actor->id,
                'prepared_at' => now(),
                'revision' => 1,
            ]);
            FinancialSettlementAccountingPostingHandoffEvent::query()->create([
                'public_id' => (string) Str::uuid(),
                'owner_organization_id' => $organizationId,
                'financial_settlement_accounting_posting_handoff_id' => $handoff->id,
                'revision' => 1,
                'event_type' => 'accounting_posting_handoff_prepared',
                'idempotency_key' => $data['idempotency_key'],
                'command_fingerprint' => $fingerprint,
                'evidence' => [
                    'reconciliation_revision' => (int) $reconciliation->revision,
                    'payment_revision' => (int) $payment->revision,
                    'accounting_posting_performed' => false,
                    'ledger_entry_created' => false,
                    'payment_modified' => false,
                    'reconciliation_modified' => false,
                    'settlement_statement_modified' => false,
                    'billing_document_modified' => false,
                    'bank_transaction_evidence_modified' => false,
                ],
                'actor_user_id' => $actor->id,
                'occurred_at' => now(),
            ]);

            return ['data' => $this->present($handoff), 'replayed' => false];
        });
    }

    /** @param array<string, mixed> $data */
    private function fingerprint(string $statementPublicId, string $paymentPublicId, int $organizationId, array $data): string
    {
        return hash('sha256', json_encode([
            'action' => 'prepare_accounting_posting_handoff',
            'statement' => $statementPublicId,
            'payment' => $paymentPublicId,
            'organization_id' => $organizationId,
            'data' => Arr::sortRecursive($data),
        ], JSON_THROW_ON_ERROR));
    }

    /** @return array<string, mixed> */
    private function present(FinancialSettlementAccountingPostingHandoff $handoff): array
    {
        return [
            'public_id' => (string) $handoff->public_id,
            'status' => (string) $handoff->status,
            'revision' => (int) $handoff->revision,
            'reconciliation_revision' => (int) $handoff->reconciliation_revision,
            'payment_revision' => (int) $handoff->payment_revision,
            'posting_date' => (string) $handoff->getRawOriginal('posting_date'),
            'accounting_reference' => $handoff->accounting_reference,
            'amount_minor' => (int) $handoff->amount_minor,
            'currency' => (string) $handoff->currency,
            'direction' => (string) $handoff->direction,
            'source_snapshot' => $handoff->source_snapshot,
            'events' => $handoff->events()->get()->toArray(),
            'accounting_posting_performed' => false,
            'ledger_entry_created' => false,
            'payment_modified' => false,
            'reconciliation_modified' => false,
            'settlement_statement_modified' => false,
            'billing_document_modified' => false,
            'bank_transaction_evidence_modified' => false,
        ];
    }
}
