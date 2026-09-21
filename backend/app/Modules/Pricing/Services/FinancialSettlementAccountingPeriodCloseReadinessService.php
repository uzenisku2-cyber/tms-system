<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\Pricing\Models\FinancialSettlementAccountingPeriod;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingEntry;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingExecution;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingHandoff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class FinancialSettlementAccountingPeriodCloseReadinessService
{
    /** @return array<string, mixed> */
    public function forPeriod(int $organizationId, string $publicId, bool $lock = false): array
    {
        $query = FinancialSettlementAccountingPeriod::query()
            ->where('owner_organization_id', $organizationId)
            ->where('public_id', $publicId);
        if ($lock) {
            $query->lockForUpdate();
        }

        return $this->evaluate($query->firstOrFail(), $lock);
    }

    /** @return array<string, mixed> */
    public function evaluate(FinancialSettlementAccountingPeriod $period, bool $lock = false): array
    {
        $organizationId = (int) $period->getAttribute('owner_organization_id');
        $currency = strtoupper((string) $period->getAttribute('currency'));
        $periodStart = (string) $period->getRawOriginal('period_start');
        $periodEnd = (string) $period->getRawOriginal('period_end');

        $handoffQuery = FinancialSettlementAccountingPostingHandoff::query()
            ->where('owner_organization_id', $organizationId)
            ->where('currency', $currency)
            ->whereDate('posting_date', '>=', $periodStart)
            ->whereDate('posting_date', '<=', $periodEnd)
            ->orderBy('id');
        $executionQuery = FinancialSettlementAccountingPostingExecution::query()
            ->where('owner_organization_id', $organizationId)
            ->where('currency', $currency)
            ->whereDate('posting_date', '>=', $periodStart)
            ->whereDate('posting_date', '<=', $periodEnd)
            ->where(static fn (Builder $query): Builder => $query
                ->whereNull('financial_settlement_accounting_posting_handoff_id')
                ->orWhereHas('handoff'))
            ->with('entries')
            ->orderBy('id');
        if ($lock) {
            $handoffQuery->lockForUpdate();
            $executionQuery->lockForUpdate();
        }

        $handoffs = $handoffQuery->get(['id', 'public_id']);
        $executions = $executionQuery->get();
        $executedHandoffIds = $executions
            ->pluck('financial_settlement_accounting_posting_handoff_id')
            ->filter(static fn (mixed $id): bool => $id !== null)
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
        $pending = $handoffs->reject(static fn (FinancialSettlementAccountingPostingHandoff $handoff): bool => in_array((int) $handoff->getKey(), $executedHandoffIds, true));
        $unbalanced = $executions->filter(fn (FinancialSettlementAccountingPostingExecution $execution): bool => ! $this->isBalanced($execution->entries));

        $blockers = [];
        if ($pending->isNotEmpty()) {
            $blockers[] = ['code' => 'pending_accounting_posting_handoffs', 'count' => $pending->count(), 'public_ids' => $pending->pluck('public_id')->values()->all()];
        }
        if ($unbalanced->isNotEmpty()) {
            $blockers[] = ['code' => 'unbalanced_accounting_posting_executions', 'count' => $unbalanced->count(), 'public_ids' => $unbalanced->pluck('public_id')->values()->all()];
        }

        return [
            'accounting_period_public_id' => (string) $period->getAttribute('public_id'),
            'period_revision' => (int) $period->getAttribute('revision'),
            'ready' => $blockers === [],
            'blockers' => $blockers,
            'summary' => [
                'handoff_count' => $handoffs->count(),
                'pending_handoff_count' => $pending->count(),
                'posting_execution_count' => $executions->count(),
                'unbalanced_posting_execution_count' => $unbalanced->count(),
            ],
        ];
    }

    /** @param Collection<int, Model> $entries */
    private function isBalanced(Collection $entries): bool
    {
        $debit = $entries->where('side', FinancialSettlementAccountingPostingEntry::SIDE_DEBIT)->sum('amount_minor');
        $credit = $entries->where('side', FinancialSettlementAccountingPostingEntry::SIDE_CREDIT)->sum('amount_minor');

        return $entries->isNotEmpty() && (int) $debit === (int) $credit;
    }
}
