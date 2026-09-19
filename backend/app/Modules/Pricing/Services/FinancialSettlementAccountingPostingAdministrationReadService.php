<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingCorrection;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingCorrectionEvent;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingEntry;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingExecution;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingExecutionEvent;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingHandoff;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingReversal;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingReversalEntry;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingReversalEvent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as ConcreteLengthAwarePaginator;

final class FinancialSettlementAccountingPostingAdministrationReadService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function index(int $organizationId, array $filters): LengthAwarePaginator
    {
        $query = FinancialSettlementAccountingPostingExecution::query()
            ->where('owner_organization_id', $organizationId)
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['direction'] ?? null, fn ($query, $value) => $query->where('direction', $value))
            ->when($filters['currency'] ?? null, fn ($query, $value) => $query->where('currency', strtoupper((string) $value)))
            ->when($filters['accounting_reference'] ?? null, fn ($query, $value) => $query->where('accounting_reference', 'like', '%'.addcslashes((string) $value, '%_\\').'%'))
            ->when($filters['from_date'] ?? null, fn ($query, $value) => $query->whereDate('posting_date', '>=', $value))
            ->when($filters['to_date'] ?? null, fn ($query, $value) => $query->whereDate('posting_date', '<=', $value))
            ->orderByDesc('executed_at')
            ->orderByDesc('id');

        $paginator = $query->paginate((int) ($filters['per_page'] ?? 25));
        $items = $paginator->getCollection()->map(
            fn (FinancialSettlementAccountingPostingExecution $execution): array => $this->serializeExecution($execution, false),
        );

        return new ConcreteLengthAwarePaginator(
            $items,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            ['path' => $paginator->path(), 'pageName' => $paginator->getPageName()],
        );
    }

    /** @return array<string, mixed> */
    public function show(int $organizationId, string $publicId): array
    {
        $execution = FinancialSettlementAccountingPostingExecution::query()
            ->where('owner_organization_id', $organizationId)
            ->where('public_id', $publicId)
            ->firstOrFail();

        return $this->serializeExecution($execution, true);
    }

    /** @return array<string, mixed> */
    private function serializeExecution(FinancialSettlementAccountingPostingExecution $execution, bool $withHistory): array
    {
        $organizationId = (int) $execution->getAttribute('owner_organization_id');
        $executionId = (int) $execution->getKey();
        $handoffId = $execution->getAttribute('financial_settlement_accounting_posting_handoff_id');
        $handoff = $handoffId === null ? null : FinancialSettlementAccountingPostingHandoff::query()
            ->where('owner_organization_id', $organizationId)
            ->find($handoffId);
        $reversal = FinancialSettlementAccountingPostingReversal::query()
            ->where('owner_organization_id', $organizationId)
            ->where('financial_settlement_accounting_posting_execution_id', $executionId)
            ->first();
        $correction = FinancialSettlementAccountingPostingCorrection::query()
            ->where('owner_organization_id', $organizationId)
            ->where(function ($query) use ($executionId): void {
                $query->where('original_execution_id', $executionId)
                    ->orWhere('replacement_execution_id', $executionId);
            })
            ->first();

        $data = [
            'public_id' => (string) $execution->getAttribute('public_id'),
            'status' => (string) $execution->getAttribute('status'),
            'role' => $correction !== null && (int) $correction->getAttribute('replacement_execution_id') === $executionId ? 'replacement' : 'original',
            'posting_date' => $this->scalar($execution->getAttribute('posting_date')),
            'executed_at' => $this->scalar($execution->getAttribute('executed_at')),
            'accounting_reference' => $execution->getAttribute('accounting_reference'),
            'direction' => $execution->getAttribute('direction'),
            'amount_minor' => (int) $execution->getAttribute('amount_minor'),
            'currency' => (string) $execution->getAttribute('currency'),
            'revision' => (int) $execution->getAttribute('revision'),
            'handoff' => $handoff === null ? null : $this->modelLink($handoff),
            'reversal' => $reversal === null ? null : $this->modelLink($reversal),
            'correction' => $correction === null ? null : $this->modelLink($correction),
        ];

        if (! $withHistory) {
            return $data;
        }

        $entries = FinancialSettlementAccountingPostingEntry::query()
            ->where('owner_organization_id', $organizationId)
            ->where('financial_settlement_accounting_posting_execution_id', $executionId)
            ->orderBy('sequence_number')
            ->get()
            ->map(fn (Model $entry): array => [
                'sequence_number' => (int) $entry->getAttribute('sequence_number'),
                'side' => (string) $entry->getAttribute('side'),
                'account_code' => (string) $entry->getAttribute('account_code'),
                'amount_minor' => (int) $entry->getAttribute('amount_minor'),
                'currency' => (string) $entry->getAttribute('currency'),
                'description' => $entry->getAttribute('description'),
            ])->all();

        $timeline = FinancialSettlementAccountingPostingExecutionEvent::query()
            ->where('owner_organization_id', $organizationId)
            ->where('financial_settlement_accounting_posting_execution_id', $executionId)
            ->get()
            ->map(fn (Model $event): array => $this->event('execution', $event))
            ->all();

        if ($reversal !== null) {
            $reversalId = (int) $reversal->getKey();
            $data['reversal']['entries'] = FinancialSettlementAccountingPostingReversalEntry::query()
                ->where('owner_organization_id', $organizationId)
                ->where('financial_settlement_accounting_posting_reversal_id', $reversalId)
                ->orderBy('sequence')
                ->get()
                ->map(fn (Model $entry): array => [
                    'sequence' => (int) $entry->getAttribute('sequence'),
                    'side' => (string) $entry->getAttribute('entry_side'),
                    'account_code' => (string) $entry->getAttribute('account_code'),
                    'amount_minor' => (int) $entry->getAttribute('amount_minor'),
                    'currency' => (string) $entry->getAttribute('currency'),
                ])->all();
            $timeline = array_merge($timeline, FinancialSettlementAccountingPostingReversalEvent::query()
                ->where('owner_organization_id', $organizationId)
                ->where('financial_settlement_accounting_posting_reversal_id', $reversalId)
                ->get()
                ->map(fn (Model $event): array => $this->event('reversal', $event))
                ->all());
        }

        if ($correction !== null) {
            $correctionId = (int) $correction->getKey();
            $data['correction']['original_execution_public_id'] = $this->executionPublicId($organizationId, (int) $correction->getAttribute('original_execution_id'));
            $data['correction']['replacement_execution_public_id'] = $this->executionPublicId($organizationId, (int) $correction->getAttribute('replacement_execution_id'));
            $timeline = array_merge($timeline, FinancialSettlementAccountingPostingCorrectionEvent::query()
                ->where('correction_id', $correctionId)
                ->get()
                ->map(fn (Model $event): array => $this->event('correction', $event))
                ->all());
        }

        usort($timeline, static fn (array $left, array $right): int => strcmp((string) ($left['occurred_at'] ?? ''), (string) ($right['occurred_at'] ?? '')));
        $data['entries'] = $entries;
        $data['timeline'] = $timeline;
        $data['source_snapshot'] = $execution->getAttribute('source_snapshot');

        return $data;
    }

    /** @return array<string, mixed> */
    private function modelLink(Model $model): array
    {
        return [
            'public_id' => (string) $model->getAttribute('public_id'),
            'status' => $model->getAttribute('status'),
            'revision' => (int) $model->getAttribute('revision'),
            'reason' => $model->getAttribute('reason'),
            'occurred_at' => $this->scalar($model->getAttribute('executed_at') ?? $model->getAttribute('reversed_at') ?? $model->getAttribute('corrected_at') ?? $model->getAttribute('prepared_at')),
        ];
    }

    /** @return array<string, mixed> */
    private function event(string $stage, Model $event): array
    {
        return [
            'stage' => $stage,
            'type' => $event->getAttribute('event_type') ?? $event->getAttribute('type'),
            'revision' => (int) $event->getAttribute('revision'),
            'occurred_at' => $this->scalar($event->getAttribute('occurred_at') ?? $event->getAttribute('created_at')),
            'payload' => $event->getAttribute('payload'),
        ];
    }

    private function executionPublicId(int $organizationId, int $executionId): ?string
    {
        $value = FinancialSettlementAccountingPostingExecution::query()
            ->where('owner_organization_id', $organizationId)
            ->whereKey($executionId)
            ->value('public_id');

        return $value === null ? null : (string) $value;
    }

    private function scalar(mixed $value): mixed
    {
        return is_object($value) && method_exists($value, 'toISOString') ? $value->toISOString() : $value;
    }
}
