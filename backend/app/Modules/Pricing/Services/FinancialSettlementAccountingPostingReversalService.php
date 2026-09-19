<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingEntry;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingExecution;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingReversal;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingReversalEntry;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingReversalEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class FinancialSettlementAccountingPostingReversalService
{
    /** @param array<string, mixed> $command */
    public function reverse(int $organizationId, string $executionPublicId, User $actor, array $command): FinancialSettlementAccountingPostingReversal
    {
        abort_unless($actor->can('compensation.manage'), 403);
        $fingerprint = $this->fingerprint($executionPublicId, $command);
        $existing = FinancialSettlementAccountingPostingReversal::query()
            ->where('owner_organization_id', $organizationId)
            ->where('idempotency_key', (string) $command['idempotency_key'])
            ->first();
        if ($existing !== null) {
            abort_if($existing->command_fingerprint !== $fingerprint, 409, 'The idempotency key was already used for another reversal command.');

            return $existing;
        }

        return DB::transaction(function () use ($organizationId, $executionPublicId, $actor, $command, $fingerprint): FinancialSettlementAccountingPostingReversal {
            $execution = FinancialSettlementAccountingPostingExecution::query()
                ->where('owner_organization_id', $organizationId)
                ->where('public_id', $executionPublicId)
                ->lockForUpdate()
                ->firstOrFail();
            abort_if((int) $execution->revision !== (int) $command['expected_revision'], 409, 'The accounting posting execution revision changed.');

            $duplicate = FinancialSettlementAccountingPostingReversal::query()
                ->where('owner_organization_id', $organizationId)
                ->where('financial_settlement_accounting_posting_execution_id', $execution->getKey())
                ->lockForUpdate()
                ->exists();
            abort_if($duplicate, 409, 'The accounting posting execution was already reversed.');

            $originalEntries = FinancialSettlementAccountingPostingEntry::query()
                ->where('financial_settlement_accounting_posting_execution_id', $execution->getKey())
                ->orderBy('sequence_number')
                ->lockForUpdate()
                ->get();
            abort_unless($originalEntries->count() === 2, 409, 'A reversible posting execution must contain exactly two entries.');
            $originalDebitMinor = 0;
            $originalCreditMinor = 0;
            foreach ($originalEntries as $originalEntry) {
                $side = (string) ($originalEntry->getAttribute('entry_side') ?? $originalEntry->getAttribute('side'));
                $amountMinor = (int) $originalEntry->getAttribute('amount_minor');
                abort_unless(in_array($side, ['debit', 'credit'], true) && $amountMinor > 0, 409, 'The original posting entry is invalid.');
                if ($side === 'debit') {
                    $originalDebitMinor += $amountMinor;
                } else {
                    $originalCreditMinor += $amountMinor;
                }
            }
            abort_unless($originalDebitMinor === $originalCreditMinor, 409, 'The original posting execution is not balanced.');

            $now = now();
            $reversal = FinancialSettlementAccountingPostingReversal::query()->create([
                'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId,
                'financial_settlement_accounting_posting_execution_id' => $execution->getKey(),
                'idempotency_key' => (string) $command['idempotency_key'], 'command_fingerprint' => $fingerprint,
                'currency' => (string) $execution->currency,
                'total_debit_minor' => $originalCreditMinor,
                'total_credit_minor' => $originalDebitMinor,
                'status' => FinancialSettlementAccountingPostingReversal::STATUS_REVERSED,
                'reason' => trim((string) $command['reason']),
                'source_snapshot' => ['execution_public_id' => (string) $execution->public_id, 'execution_revision' => (int) $execution->revision],
                'revision' => 1, 'reversed_by_user_id' => $actor->getKey(), 'reversed_at' => $now,
            ]);

            foreach ($originalEntries as $index => $originalEntry) {
                $originalSide = (string) ($originalEntry->getAttribute('entry_side') ?? $originalEntry->getAttribute('side'));
                abort_unless(in_array($originalSide, ['debit', 'credit'], true), 409, 'The original posting entry side is invalid.');
                FinancialSettlementAccountingPostingReversalEntry::query()->create([
                    'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId,
                    'financial_settlement_accounting_posting_reversal_id' => $reversal->getKey(),
                    'original_posting_entry_id' => $originalEntry->getKey(), 'sequence' => $index + 1,
                    'entry_side' => $originalSide === 'debit' ? 'credit' : 'debit',
                    'account_code' => (string) $originalEntry->account_code,
                    'amount_minor' => (int) $originalEntry->amount_minor, 'currency' => (string) $originalEntry->currency,
                    'source_snapshot' => ['original_entry_public_id' => (string) $originalEntry->public_id, 'original_entry_side' => $originalSide],
                ]);
            }

            FinancialSettlementAccountingPostingReversalEvent::query()->create([
                'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId,
                'financial_settlement_accounting_posting_reversal_id' => $reversal->getKey(),
                'event_type' => FinancialSettlementAccountingPostingReversalEvent::TYPE_REVERSED,
                'revision' => 1,
                'payload' => ['execution_public_id' => (string) $execution->public_id, 'reason' => (string) $command['reason']],
                'actor_user_id' => $actor->getKey(), 'occurred_at' => $now,
            ]);

            return $reversal;
        }, 3);
    }

    /** @param array<string, mixed> $command */
    private function fingerprint(string $executionPublicId, array $command): string
    {
        return hash('sha256', json_encode([
            'execution_public_id' => $executionPublicId,
            'expected_revision' => (int) $command['expected_revision'],
            'reason' => trim((string) $command['reason']),
        ], JSON_THROW_ON_ERROR));
    }
}
