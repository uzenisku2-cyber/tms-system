<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Services;

use App\Modules\Drivers\Models\Driver;
use App\Modules\Fuel\Models\FuelTransaction;
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
        $query = FuelTransaction::query()
            ->where('owner_organization_id', $organizationId)
            ->with([
                'importedDriver:id,first_name,last_name',
                'actualDriver:id,first_name,last_name',
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
            ],
        ];
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
            'source_row' => (int) $transaction->source_row,
        ];
    }
}
