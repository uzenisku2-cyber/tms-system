<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\Pricing\Models\FinancialSettlementAccountingPeriod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as ConcreteLengthAwarePaginator;

final class FinancialSettlementAccountingPeriodAdministrationReadService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function index(int $organizationId, array $filters): LengthAwarePaginator
    {
        $query = FinancialSettlementAccountingPeriod::query()
            ->where('owner_organization_id', $organizationId)
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['currency'] ?? null, fn ($query, $value) => $query->where('currency', strtoupper((string) $value)))
            ->when($filters['from_date'] ?? null, fn ($query, $value) => $query->whereDate('period_end', '>=', $value))
            ->when($filters['to_date'] ?? null, fn ($query, $value) => $query->whereDate('period_start', '<=', $value))
            ->withCount('events')
            ->orderByDesc('period_start')
            ->orderByDesc('id');

        $paginator = $query->paginate((int) ($filters['per_page'] ?? 25));
        $items = $paginator->getCollection()->map(
            fn (FinancialSettlementAccountingPeriod $period): array => $this->serializePeriod($period, false),
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
        $period = FinancialSettlementAccountingPeriod::query()
            ->where('owner_organization_id', $organizationId)
            ->where('public_id', $publicId)
            ->with('events')
            ->firstOrFail();

        return $this->serializePeriod($period, true);
    }

    /** @return array<string, mixed> */
    private function serializePeriod(FinancialSettlementAccountingPeriod $period, bool $withEvents): array
    {
        $data = [
            'public_id' => (string) $period->getAttribute('public_id'),
            'period_start' => $this->date($period->getAttribute('period_start')),
            'period_end' => $this->date($period->getAttribute('period_end')),
            'currency' => (string) $period->getAttribute('currency'),
            'status' => (string) $period->getAttribute('status'),
            'revision' => (int) $period->getAttribute('revision'),
            'last_reason' => $period->getAttribute('last_reason'),
            'closed_at' => $this->dateTime($period->getAttribute('closed_at')),
            'reopened_at' => $this->dateTime($period->getAttribute('reopened_at')),
            'event_count' => (int) ($period->getAttribute('events_count') ?? $period->events()->count()),
        ];

        if ($withEvents) {
            $data['events'] = $period->events
                ->map(fn (Model $event): array => [
                    'event_type' => (string) $event->getAttribute('event_type'),
                    'revision' => (int) $event->getAttribute('revision'),
                    'payload' => $event->getAttribute('payload'),
                    'occurred_at' => $this->dateTime($event->getAttribute('occurred_at')),
                    'actor_user_id' => $event->getAttribute('actor_user_id'),
                ])->all();
        }

        return $data;
    }

    private function date(mixed $value): ?string
    {
        return is_object($value) && method_exists($value, 'format') ? $value->format('Y-m-d') : ($value === null ? null : (string) $value);
    }

    private function dateTime(mixed $value): ?string
    {
        return is_object($value) && method_exists($value, 'toISOString') ? $value->toISOString() : ($value === null ? null : (string) $value);
    }
}
