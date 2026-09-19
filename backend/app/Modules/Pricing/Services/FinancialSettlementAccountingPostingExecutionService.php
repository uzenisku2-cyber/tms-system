<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingEntry;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingExecution;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingExecutionEvent;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingHandoff;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FinancialSettlementAccountingPostingExecutionService
{
    /** @param array<string, mixed> $data @return array{data: array<string, mixed>, replayed: bool} */
    public function execute(string $handoffPublicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('compensation.manage'), 403);
        $fingerprint = hash('sha256', json_encode(['action' => 'execute_settlement_accounting_posting', 'handoff' => $handoffPublicId, 'organization_id' => $organizationId, 'data' => Arr::sortRecursive($data)], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($handoffPublicId, $data, $organizationId, $actor, $fingerprint): array {
            $replay = FinancialSettlementAccountingPostingExecution::query()->where('owner_organization_id', $organizationId)->where('idempotency_key', $data['idempotency_key'])->first();
            if ($replay instanceof FinancialSettlementAccountingPostingExecution) {
                if (! hash_equals((string) $replay->command_fingerprint, $fingerprint)) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key was already used with a different command.']]);
                }

                return ['data' => $this->present($replay), 'replayed' => true];
            }

            $handoff = FinancialSettlementAccountingPostingHandoff::query()->where('public_id', $handoffPublicId)->where('owner_organization_id', $organizationId)->lockForUpdate()->firstOrFail();
            if ($handoff->status !== FinancialSettlementAccountingPostingHandoff::STATUS_PREPARED) {
                throw ValidationException::withMessages(['handoff' => ['Only a prepared accounting posting handoff may be executed.']]);
            }
            if ((int) $handoff->revision !== (int) $data['expected_handoff_revision']) {
                throw ValidationException::withMessages(['expected_handoff_revision' => ['The accounting posting handoff revision is stale.']]);
            }
            if (FinancialSettlementAccountingPostingExecution::query()->where('financial_settlement_accounting_posting_handoff_id', $handoff->id)->exists()) {
                throw ValidationException::withMessages(['handoff' => ['This accounting posting handoff has already been executed.']]);
            }

            $execution = FinancialSettlementAccountingPostingExecution::query()->create([
                'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId,
                'financial_settlement_accounting_posting_handoff_id' => $handoff->id,
                'idempotency_key' => $data['idempotency_key'], 'command_fingerprint' => $fingerprint,
                'handoff_revision' => $handoff->revision, 'posting_date' => $handoff->getRawOriginal('posting_date'),
                'accounting_reference' => $handoff->accounting_reference, 'amount_minor' => $handoff->amount_minor,
                'currency' => $handoff->currency, 'direction' => $handoff->direction,
                'status' => FinancialSettlementAccountingPostingExecution::STATUS_POSTED, 'description' => $data['description'],
                'source_snapshot' => ['handoff_public_id' => $handoff->public_id, 'handoff_revision' => (int) $handoff->revision, 'handoff_source_snapshot' => $handoff->source_snapshot, 'debit_account_code' => $data['debit_account_code'], 'credit_account_code' => $data['credit_account_code']],
                'executed_by_user_id' => $actor->id, 'executed_at' => now(), 'revision' => 1,
            ]);

            foreach ([[1, FinancialSettlementAccountingPostingEntry::SIDE_DEBIT, $data['debit_account_code']], [2, FinancialSettlementAccountingPostingEntry::SIDE_CREDIT, $data['credit_account_code']]] as [$sequence, $side, $account]) {
                FinancialSettlementAccountingPostingEntry::query()->create([
                    'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId,
                    'financial_settlement_accounting_posting_execution_id' => $execution->id,
                    'sequence_number' => $sequence, 'side' => $side, 'account_code' => $account,
                    'amount_minor' => $handoff->amount_minor, 'currency' => $handoff->currency,
                    'description' => $data['description'], 'occurred_at' => now(),
                ]);
            }
            FinancialSettlementAccountingPostingExecutionEvent::query()->create([
                'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId,
                'financial_settlement_accounting_posting_execution_id' => $execution->id, 'revision' => 1,
                'event_type' => 'financial_settlement_accounting_posted', 'idempotency_key' => $data['idempotency_key'],
                'command_fingerprint' => $fingerprint,
                'evidence' => ['handoff_revision' => (int) $handoff->revision, 'entry_count' => 2, 'debit_amount_minor' => (int) $handoff->amount_minor, 'credit_amount_minor' => (int) $handoff->amount_minor, 'balanced' => true, 'payment_modified' => false, 'reconciliation_modified' => false, 'settlement_statement_modified' => false, 'billing_document_modified' => false, 'bank_transaction_evidence_modified' => false],
                'actor_user_id' => $actor->id, 'occurred_at' => now(),
            ]);

            return ['data' => $this->present($execution), 'replayed' => false];
        });
    }

    /** @return array<string, mixed> */
    private function present(FinancialSettlementAccountingPostingExecution $execution): array
    {
        $entries = $execution->entries()->get();

        return ['public_id' => (string) $execution->public_id, 'status' => (string) $execution->status, 'revision' => (int) $execution->revision, 'handoff_revision' => (int) $execution->handoff_revision, 'posting_date' => (string) $execution->getRawOriginal('posting_date'), 'accounting_reference' => $execution->accounting_reference, 'amount_minor' => (int) $execution->amount_minor, 'currency' => (string) $execution->currency, 'direction' => (string) $execution->direction, 'entries' => $entries->toArray(), 'events' => $execution->events()->get()->toArray(), 'balanced' => $entries->where('side', FinancialSettlementAccountingPostingEntry::SIDE_DEBIT)->sum('amount_minor') === $entries->where('side', FinancialSettlementAccountingPostingEntry::SIDE_CREDIT)->sum('amount_minor'), 'payment_modified' => false, 'reconciliation_modified' => false, 'settlement_statement_modified' => false, 'billing_document_modified' => false, 'bank_transaction_evidence_modified' => false];
    }
}
