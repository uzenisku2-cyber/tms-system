<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Services;

use App\Modules\Drivers\Models\Driver;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Fuel\Models\FuelTransactionReconciliation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class FuelTransactionAdministrationService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function index(int $organizationId, array $filters): array
    {
        $query = $this->baseQuery($organizationId)
            ->with([
                'importedDriver:id,first_name,last_name',
                'actualDriver:id,first_name,last_name',
                'reconciliation:id,fuel_transaction_id,status,result_code,candidate_count,matched_daily_report_id,revision,evaluated_at,resolved_at',
            ]);

        $this->applyFilters($query, $filters);

        $perPage = (int) ($filters['per_page'] ?? 25);
        $page = (int) ($filters['page'] ?? 1);
        $paginator = $query
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (FuelTransaction $transaction): array => $this->item($transaction))
                ->values()
                ->all(),
            'pagination' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'filters' => [
                'providers' => ['ORLEN', 'MOL'],
                'drivers' => $this->filterDrivers($organizationId),
                'per_page_options' => [15, 25, 50, 100],
                'reconciliation_statuses' => [
                    FuelTransactionReconciliation::STATUS_PENDING,
                    FuelTransactionReconciliation::STATUS_MATCHED,
                    FuelTransactionReconciliation::STATUS_REVIEW_REQUIRED,
                    FuelTransactionReconciliation::STATUS_RESOLVED,
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function overview(int $organizationId, array $filters): array
    {
        $query = $this->baseQuery($organizationId);
        $this->applyFilters($query, $filters);

        $pending = $this->statusCount($query, FuelTransactionReconciliation::STATUS_PENDING);
        $matched = $this->statusCount($query, FuelTransactionReconciliation::STATUS_MATCHED);
        $reviewRequired = $this->statusCount($query, FuelTransactionReconciliation::STATUS_REVIEW_REQUIRED);
        $resolved = $this->statusCount($query, FuelTransactionReconciliation::STATUS_RESOLVED);

        $providers = (clone $query)
            ->selectRaw('provider, COUNT(*) as transaction_count')
            ->groupBy('provider')
            ->orderBy('provider')
            ->get()
            ->map(fn (FuelTransaction $transaction): array => [
                'provider' => (string) $transaction->provider,
                'transaction_count' => (int) $transaction->getAttribute('transaction_count'),
            ])
            ->values()
            ->all();

        $currencyTotals = (clone $query)
            ->selectRaw('currency, COUNT(*) as transaction_count, COALESCE(SUM(gross_amount), 0) as gross_amount')
            ->groupBy('currency')
            ->orderBy('currency')
            ->get()
            ->map(fn (FuelTransaction $transaction): array => [
                'currency' => (string) $transaction->currency,
                'transaction_count' => (int) $transaction->getAttribute('transaction_count'),
                'gross_amount' => (string) $transaction->getAttribute('gross_amount'),
            ])
            ->values()
            ->all();

        $driverRows = (clone $query)
            ->selectRaw('COALESCE(actual_driver_id, driver_id) as effective_driver_id, COUNT(*) as transaction_count')
            ->groupByRaw('COALESCE(actual_driver_id, driver_id)')
            ->get();
        $driverIds = $driverRows->pluck('effective_driver_id')->filter()->map(fn (mixed $id): int => (int) $id)->all();
        $driverNames = Driver::query()
            ->whereIn('id', $driverIds)
            ->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn (Driver $driver): array => [(int) $driver->getKey() => $driver->full_name]);
        $drivers = $driverRows
            ->map(function (FuelTransaction $transaction) use ($driverNames): array {
                $driverId = $transaction->getAttribute('effective_driver_id');
                $normalizedId = $driverId === null ? null : (int) $driverId;

                return [
                    'driver_id' => $normalizedId,
                    'driver_name' => $normalizedId === null ? null : $driverNames->get($normalizedId),
                    'transaction_count' => (int) $transaction->getAttribute('transaction_count'),
                ];
            })
            ->sortBy(fn (array $driver): string => (string) ($driver['driver_name'] ?? ''))
            ->values()
            ->all();

        return [
            'summary' => [
                'total' => (int) (clone $query)->count(),
                'pending' => $pending,
                'matched' => $matched,
                'review_required' => $reviewRequired,
                'resolved' => $resolved,
                'attention_required' => $pending + $reviewRequired,
            ],
            'providers' => $providers,
            'drivers' => $drivers,
            'currency_totals' => $currencyTotals,
        ];
    }

    /** @return Builder<FuelTransaction> */
    private function baseQuery(int $organizationId): Builder
    {
        return FuelTransaction::query()->where('owner_organization_id', $organizationId);
    }

    private function statusCount(Builder $query, string $status): int
    {
        $statusQuery = clone $query;
        if ($status === FuelTransactionReconciliation::STATUS_PENDING) {
            $statusQuery->where(function (Builder $pendingQuery): void {
                $pendingQuery->whereDoesntHave('reconciliation')
                    ->orWhereHas('reconciliation', fn (Builder $reconciliationQuery): Builder => $reconciliationQuery->where('status', FuelTransactionReconciliation::STATUS_PENDING));
            });
        } else {
            $statusQuery->whereHas('reconciliation', fn (Builder $reconciliationQuery): Builder => $reconciliationQuery->where('status', $status));
        }

        return (int) $statusQuery->count();
    }

    /** @param array<string, mixed> $filters */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (is_string($filters['date_from'] ?? null)) {
            $query->whereDate('occurred_at', '>=', $filters['date_from']);
        }
        if (is_string($filters['date_to'] ?? null)) {
            $query->whereDate('occurred_at', '<=', $filters['date_to']);
        }
        if (is_string($filters['provider'] ?? null)) {
            $query->where('provider', $filters['provider']);
        }
        if (is_numeric($filters['driver_id'] ?? null)) {
            $driverId = (int) $filters['driver_id'];
            $query->where(function (Builder $driverQuery) use ($driverId): void {
                $driverQuery->where('actual_driver_id', $driverId)
                    ->orWhere(function (Builder $importedQuery) use ($driverId): void {
                        $importedQuery->whereNull('actual_driver_id')->where('driver_id', $driverId);
                    });
            });
        }
        if (is_string($filters['reconciliation_status'] ?? null)) {
            $status = $filters['reconciliation_status'];
            if ($status === FuelTransactionReconciliation::STATUS_PENDING) {
                $query->where(function (Builder $reconciliationQuery): void {
                    $reconciliationQuery->whereDoesntHave('reconciliation')
                        ->orWhereHas('reconciliation', fn (Builder $projectionQuery): Builder => $projectionQuery->where('status', FuelTransactionReconciliation::STATUS_PENDING));
                });
            } else {
                $query->whereHas('reconciliation', fn (Builder $reconciliationQuery): Builder => $reconciliationQuery->where('status', $status));
            }
        }
        if (is_string($filters['card'] ?? null) && trim($filters['card']) !== '') {
            $query->where('provider_card_identifier', 'like', '%'.trim($filters['card']).'%');
        }
        if (is_string($filters['search'] ?? null) && trim($filters['search']) !== '') {
            $search = '%'.trim($filters['search']).'%';
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery->where('station_name', 'like', $search)
                    ->orWhere('product_name', 'like', $search)
                    ->orWhere('vehicle_registration', 'like', $search)
                    ->orWhere('provider_transaction_identifier', 'like', $search);
            });
        }
    }

    /** @return array<int, array{id: int, name: string}> */
    private function filterDrivers(int $organizationId): array
    {
        $transactions = FuelTransaction::query()
            ->where('owner_organization_id', $organizationId)
            ->get(['driver_id', 'actual_driver_id']);
        $driverIds = $transactions
            ->flatMap(fn (FuelTransaction $transaction): array => [
                $transaction->driver_id,
                $transaction->actual_driver_id,
            ])
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        return Driver::query()
            ->whereIn('id', $driverIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (Driver $driver): array => [
                'id' => (int) $driver->getKey(),
                'name' => $driver->full_name,
            ])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function item(FuelTransaction $transaction): array
    {
        $cardIdentifier = (string) $transaction->provider_card_identifier;
        $effectiveDriver = $transaction->actualDriver ?? $transaction->importedDriver;
        $reconciliation = $transaction->getRelation('reconciliation');
        $reconciliationSummary = $reconciliation instanceof FuelTransactionReconciliation
            ? [
                'status' => $reconciliation->status,
                'result_code' => $reconciliation->result_code,
                'candidate_count' => (int) $reconciliation->candidate_count,
                'matched_daily_report_id' => $reconciliation->matched_daily_report_id === null ? null : (int) $reconciliation->matched_daily_report_id,
                'revision' => (int) $reconciliation->revision,
            ]
            : [
                'status' => FuelTransactionReconciliation::STATUS_PENDING,
                'result_code' => null,
                'candidate_count' => 0,
                'matched_daily_report_id' => null,
                'revision' => 0,
            ];

        return [
            'public_id' => $transaction->public_id,
            'occurred_at' => $transaction->occurred_at?->format('Y-m-d H:i:s'),
            'posting_date' => $transaction->posting_date?->toDateString(),
            'provider' => $transaction->provider,
            'provider_transaction_identifier' => $transaction->provider_transaction_identifier,
            'station_name' => $transaction->station_name,
            'product_name' => $transaction->product_name,
            'quantity' => $transaction->quantity,
            'unit_of_measure' => $transaction->unit_of_measure,
            'gross_amount' => $transaction->gross_amount,
            'currency' => $transaction->currency,
            'masked_card' => $cardIdentifier === '' ? null : '**** '.Str::substr($cardIdentifier, -4),
            'imported_driver' => $transaction->importedDriver === null ? null : [
                'id' => (int) $transaction->importedDriver->getKey(),
                'name' => $transaction->importedDriver->full_name,
            ],
            'actual_driver' => $transaction->actualDriver === null ? null : [
                'id' => (int) $transaction->actualDriver->getKey(),
                'name' => $transaction->actualDriver->full_name,
            ],
            'effective_driver' => $effectiveDriver === null ? null : [
                'id' => (int) $effectiveDriver->getKey(),
                'name' => $effectiveDriver->full_name,
            ],
            'driver_attribution_revision' => (int) $transaction->driver_attribution_revision,
            'vehicle_registration' => $transaction->vehicle_registration,
            'odometer' => $transaction->odometer,
            'match_status' => $transaction->match_status,
            'reconciliation' => $reconciliationSummary,
            'source_row' => (int) $transaction->source_row,
        ];
    }
}
