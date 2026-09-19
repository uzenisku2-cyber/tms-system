<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingCorrection;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingCorrectionEvent;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingEntry;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingExecution;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingReversal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FinancialSettlementAccountingPostingCorrectionService
{
    /** @param array<string, mixed> $command */
    public function correct(int $organizationId, string $executionPublicId, User $actor, array $command): FinancialSettlementAccountingPostingCorrection
    {
        $normalized = $this->normalize($command);
        $fingerprint = hash('sha256', json_encode($normalized, JSON_THROW_ON_ERROR));
        $existing = FinancialSettlementAccountingPostingCorrection::query()
            ->where('owner_organization_id', $organizationId)
            ->where('idempotency_key', (string) $command['idempotency_key'])
            ->first();
        if ($existing !== null) {
            abort_if($existing->command_fingerprint !== $fingerprint, 409, 'The idempotency key was already used for another correction command.');

            return $existing;
        }

        return DB::transaction(function () use ($organizationId, $executionPublicId, $actor, $command, $fingerprint): FinancialSettlementAccountingPostingCorrection {
            $original = FinancialSettlementAccountingPostingExecution::query()
                ->where('owner_organization_id', $organizationId)
                ->where('public_id', $executionPublicId)
                ->lockForUpdate()
                ->firstOrFail();
            abort_if((int) $original->revision !== (int) $command['expected_revision'], 409, 'The accounting posting execution revision changed.');

            $reversal = FinancialSettlementAccountingPostingReversal::query()
                ->where('owner_organization_id', $organizationId)
                ->where('financial_settlement_accounting_posting_execution_id', $original->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            abort_if(
                FinancialSettlementAccountingPostingCorrection::query()
                    ->where('owner_organization_id', $organizationId)
                    ->where('original_execution_id', $original->getKey())
                    ->lockForUpdate()
                    ->exists(),
                409,
                'The accounting posting execution already has a correction.',
            );

            $entries = collect($command['entries']);
            $debit = (int) $entries->where('side', 'debit')->sum('amount_minor');
            $credit = (int) $entries->where('side', 'credit')->sum('amount_minor');
            if ($debit <= 0 || $debit !== $credit) {
                throw ValidationException::withMessages(['entries' => 'Replacement accounting entries must contain equal positive debit and credit totals.']);
            }

            $replacement = FinancialSettlementAccountingPostingExecution::query()->create([
                'public_id' => (string) Str::uuid(),
                'owner_organization_id' => $organizationId,
                'financial_settlement_accounting_posting_handoff_id' => null,
                'idempotency_key' => (string) Str::uuid(),
                'command_fingerprint' => $fingerprint,
                'handoff_revision' => (int) $original->handoff_revision,
                'posting_date' => $command['posting_date'],
                'accounting_reference' => $command['accounting_reference'],
                'amount_minor' => $debit,
                'currency' => (string) $original->currency,
                'direction' => (string) $original->direction,
                'status' => FinancialSettlementAccountingPostingExecution::STATUS_POSTED,
                'description' => $command['description'] ?? null,
                'source_snapshot' => [
                    'correction_of_execution_public_id' => $original->public_id,
                    'reversal_public_id' => $reversal->public_id,
                    'reason' => $command['reason'],
                ],
                'executed_by_user_id' => $actor->getKey(),
                'executed_at' => now(),
                'revision' => 1,
            ]);

            foreach ($entries->values() as $index => $entry) {
                FinancialSettlementAccountingPostingEntry::query()->create([
                    'public_id' => (string) Str::uuid(),
                    'owner_organization_id' => $organizationId,
                    'financial_settlement_accounting_posting_execution_id' => $replacement->getKey(),
                    'sequence_number' => $index + 1,
                    'side' => $entry['side'],
                    'account_code' => $entry['account_code'],
                    'amount_minor' => $entry['amount_minor'],
                    'currency' => (string) $original->currency,
                    'description' => $entry['description'] ?? null,
                    'occurred_at' => now(),
                ]);
            }

            $correction = FinancialSettlementAccountingPostingCorrection::query()->create([
                'public_id' => (string) Str::uuid(),
                'owner_organization_id' => $organizationId,
                'original_execution_id' => $original->getKey(),
                'reversal_id' => $reversal->getKey(),
                'replacement_execution_id' => $replacement->getKey(),
                'idempotency_key' => $command['idempotency_key'],
                'command_fingerprint' => $fingerprint,
                'reason' => $command['reason'],
                'source_snapshot' => ['entries' => $command['entries'], 'posting_date' => $command['posting_date']],
                'corrected_by_user_id' => $actor->getKey(),
                'corrected_at' => now(),
                'revision' => 1,
            ]);
            FinancialSettlementAccountingPostingCorrectionEvent::query()->create([
                'public_id' => (string) Str::uuid(),
                'correction_id' => $correction->getKey(),
                'event_type' => 'accounting_posting_corrected',
                'payload' => ['original_execution_public_id' => $original->public_id, 'reversal_public_id' => $reversal->public_id, 'replacement_execution_public_id' => $replacement->public_id],
                'actor_user_id' => $actor->getKey(),
                'occurred_at' => now(),
                'revision' => 1,
            ]);

            return $correction;
        });
    }

    /** @param array<string, mixed> $command @return array<string, mixed> */
    private function normalize(array $command): array
    {
        $entries = collect($command['entries'])->map(static fn (array $entry): array => [
            'side' => $entry['side'],
            'account_code' => $entry['account_code'],
            'amount_minor' => (int) $entry['amount_minor'],
            'description' => $entry['description'] ?? null,
        ])->values()->all();

        return [
            'expected_revision' => (int) $command['expected_revision'],
            'reason' => $command['reason'],
            'posting_date' => $command['posting_date'],
            'accounting_reference' => $command['accounting_reference'],
            'description' => $command['description'] ?? null,
            'entries' => $entries,
        ];
    }
}
