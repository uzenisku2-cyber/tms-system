<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Core\Organizations\OrganizationContext;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Organizations\Models\Organization;
use Carbon\CarbonImmutable;
use DateTimeInterface;

final class DriverPerformanceOverviewService
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function overview(array $filters): array
    {
        $organizationId = $this->organizationContext->requireId();

        $reports = DailyReport::query()
            ->with('performedByDriver')
            ->forOrganization($organizationId)
            ->orderBy('service_date')
            ->orderBy('id')
            ->get();

        $driverIds = $reports
            ->pluck('performed_by_driver_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $assignmentsByDriver = $this->assignmentsByDriver($driverIds);

        /** @var list<array<string, mixed>> $records */
        $records = [];

        foreach ($reports as $report) {
            $serviceDate = $this->serviceDate($report);
            $reportDriverId = (int) $report->getAttribute(
                'performed_by_driver_id',
            );

            $records[] = [
                'report' => $report,
                'service_date' => $serviceDate,
                'driver_id' => $reportDriverId,
                'carrier' => $this->carrierAttribution(
                    $organizationId,
                    $serviceDate,
                    $assignmentsByDriver[$reportDriverId] ?? [],
                ),
            ];
        }

        $driverId = $filters['performed_by_driver_id'] ?? null;
        $carrierScope = $filters['carrier_scope'] ?? 'all';
        $carrierOrganizationId = $filters['carrier_organization_id'] ?? null;

        if (! is_string($carrierScope)) {
            $carrierScope = 'all';
        }

        $entityRecords = array_values(array_filter(
            $records,
            fn (array $record): bool => $this->matchesDriver($record, $driverId)
                && $this->matchesCarrier(
                    $record,
                    $carrierScope,
                    $carrierOrganizationId,
                ),
        ));

        $quickPeriods = $this->quickPeriods($entityRecords);
        [$period, $dateFrom, $dateTo] = $this->resolvePeriod($filters);

        $filteredRecords = array_values(array_filter(
            $entityRecords,
            static function (array $record) use ($dateFrom, $dateTo): bool {
                $serviceDate = (string) $record['service_date'];

                if (
                    is_string($dateFrom)
                    && $dateFrom !== ''
                    && $serviceDate < $dateFrom
                ) {
                    return false;
                }

                return ! (
                    is_string($dateTo)
                    && $dateTo !== ''
                    && $serviceDate > $dateTo
                );
            },
        ));

        $groupBy = ($filters['group_by'] ?? null) === 'day'
            ? 'day'
            : 'month';

        $totals = new DriverPerformanceAccumulator;

        /**
         * @var array<int, array{
         *     driver:array<string, int|string|bool|null>,
         *     metrics:DriverPerformanceAccumulator,
         *     carriers:array<string, array<string, mixed>>
         * }> $drivers
         */
        $drivers = [];

        /** @var array<string, DriverPerformanceAccumulator> $timeline */
        $timeline = [];

        $attributedRouteCount = 0;
        $unattributedRouteCount = 0;

        foreach ($filteredRecords as $record) {
            /** @var DailyReport $report */
            $report = $record['report'];
            $serviceDate = (string) $record['service_date'];
            $reportDriverId = (int) $record['driver_id'];
            /** @var array<string, mixed> $carrier */
            $carrier = $record['carrier'];
            $values = $this->values($report);

            $totals->add($serviceDate, ...$values);

            if ($carrier['scope'] === 'unattributed') {
                $unattributedRouteCount++;
            } else {
                $attributedRouteCount++;
            }

            if (! array_key_exists($reportDriverId, $drivers)) {
                $driver = $report->getRelation('performedByDriver');

                $drivers[$reportDriverId] = [
                    'driver' => $this->driverIdentity(
                        $reportDriverId,
                        $driver instanceof Driver ? $driver : null,
                    ),
                    'metrics' => new DriverPerformanceAccumulator,
                    'carriers' => [],
                ];
            }

            $drivers[$reportDriverId]['metrics']->add(
                $serviceDate,
                ...$values,
            );

            $drivers[$reportDriverId]['carriers'][
                (string) $carrier['key']
            ] = $carrier;

            $timelinePeriod = $groupBy === 'day'
                ? $serviceDate
                : substr($serviceDate, 0, 7);

            if (! array_key_exists($timelinePeriod, $timeline)) {
                $timeline[$timelinePeriod] = new DriverPerformanceAccumulator;
            }

            $timeline[$timelinePeriod]->add($serviceDate, ...$values);
        }

        ksort($drivers, SORT_NUMERIC);
        ksort($timeline, SORT_STRING);

        $driverRows = [];

        foreach ($drivers as $driver) {
            $carrierRows = array_values($driver['carriers']);

            usort(
                $carrierRows,
                static fn (array $left, array $right): int => strnatcasecmp(
                    (string) $left['name'],
                    (string) $right['name'],
                ),
            );

            $driverRows[] = [
                'driver' => $driver['driver'],
                'carriers' => $carrierRows,
                'metrics' => $driver['metrics']->metrics(),
            ];
        }

        $timelineRows = [];

        foreach ($timeline as $timelinePeriod => $metrics) {
            $timelineRows[] = [
                'period' => $timelinePeriod,
                'metrics' => $metrics->metrics(),
            ];
        }

        $driverOptionRecords = array_values(array_filter(
            $records,
            fn (array $record): bool => $this->matchesCarrier(
                $record,
                $carrierScope,
                $carrierOrganizationId,
            ),
        ));

        $carrierOptionRecords = array_values(array_filter(
            $records,
            fn (array $record): bool => $this->matchesDriver(
                $record,
                $driverId,
            ),
        ));

        return [
            'scope' => [
                'organization_id' => $organizationId,
                'record_scope' => 'active_daily_reports',
                'status_scope' => 'all_statuses',
                'financial_eligibility_applied' => false,
                'quality_profile_applied' => false,
                'carrier_attribution_source' => 'driver_assignment_effective_on_service_date',
            ],
            'filters' => [
                'performed_by_driver_id' => $driverId,
                'carrier_scope' => $carrierScope,
                'carrier_organization_id' => $carrierOrganizationId,
                'period' => $period,
                'service_date_from' => $dateFrom,
                'service_date_to' => $dateTo,
                'group_by' => $groupBy,
            ],
            'filter_options' => [
                'date_bounds' => $this->dateBounds($records),
                'quick_periods' => $quickPeriods,
                'drivers' => $this->driverOptions($driverOptionRecords),
                'carriers' => $this->carrierOptions($carrierOptionRecords),
            ],
            'source_contract' => [
                'customer_rejected_source' => 'undelivered_parcels',
                'processed_parcels' => 'delivered + redirected + customer_rejected',
                'not_delivered_parcels' => 'loaded - processed',
                'processed_share_percent' => 'processed / loaded * 100',
                'redirected_share_percent' => 'redirected / loaded * 100',
                'percentage_aggregation' => 'ratio_of_aggregated_counts',
                'driver_carrier_attribution' => 'assignment valid on service_date',
            ],
            'carrier_attribution' => [
                'attributed_route_count' => $attributedRouteCount,
                'unattributed_route_count' => $unattributedRouteCount,
            ],
            'totals' => $totals->metrics(),
            'drivers' => $driverRows,
            'timeline' => $timelineRows,
        ];
    }

    /**
     * @param  list<int>  $driverIds
     * @return array<int, list<DriverOrganizationAssignment>>
     */
    private function assignmentsByDriver(array $driverIds): array
    {
        if ($driverIds === []) {
            return [];
        }

        $assignments = DriverOrganizationAssignment::query()
            ->with('organization')
            ->whereIn('driver_id', $driverIds)
            ->orderBy('valid_from')
            ->orderBy('id')
            ->get();

        /** @var array<int, list<DriverOrganizationAssignment>> $result */
        $result = [];

        foreach ($assignments as $assignment) {
            $driverId = (int) $assignment->getAttribute('driver_id');
            $result[$driverId][] = $assignment;
        }

        return $result;
    }

    /**
     * @param  list<DriverOrganizationAssignment>  $assignments
     * @return array<string, int|string|null>
     */
    private function carrierAttribution(
        int $currentOrganizationId,
        string $serviceDate,
        array $assignments,
    ): array {
        $effectiveAssignment = null;

        foreach ($assignments as $assignment) {
            $validFrom = $this->dateValue(
                $assignment->getAttribute('valid_from'),
            );
            $validUntil = $this->nullableDateValue(
                $assignment->getAttribute('valid_until'),
            );

            if (
                $validFrom <= $serviceDate
                && ($validUntil === null || $validUntil >= $serviceDate)
            ) {
                $effectiveAssignment = $assignment;
            }
        }

        if (! $effectiveAssignment instanceof DriverOrganizationAssignment) {
            return [
                'key' => 'unattributed',
                'scope' => 'unattributed',
                'organization_id' => null,
                'name' => 'Bez historicky doloženého dopravce',
                'type' => null,
            ];
        }

        $organizationId = (int) $effectiveAssignment->getAttribute(
            'organization_id',
        );
        $organization = $effectiveAssignment->getRelation('organization');
        $own = $organizationId === $currentOrganizationId;

        return [
            'key' => $own ? 'own' : 'organization:'.$organizationId,
            'scope' => $own ? 'own' : 'external',
            'organization_id' => $organizationId,
            'name' => $own
                ? 'Vlastní řidiči'
                : ($organization instanceof Organization
                    ? (string) $organization->getAttribute('name')
                    : 'Dopravce #'.$organizationId),
            'type' => $organization instanceof Organization
                ? (string) $organization->getAttribute('type')
                : null,
        ];
    }

    /** @param array<string, mixed> $record */
    private function matchesDriver(array $record, mixed $driverId): bool
    {
        return ! is_int($driverId)
            || $driverId <= 0
            || (int) $record['driver_id'] === $driverId;
    }

    /** @param array<string, mixed> $record */
    private function matchesCarrier(
        array $record,
        string $carrierScope,
        mixed $carrierOrganizationId,
    ): bool {
        if ($carrierScope === 'all') {
            return true;
        }

        /** @var array<string, mixed> $carrier */
        $carrier = $record['carrier'];

        if ($carrierScope !== 'external') {
            return $carrier['scope'] === $carrierScope;
        }

        return $carrier['scope'] === 'external'
            && is_int($carrierOrganizationId)
            && $carrierOrganizationId > 0
            && (int) $carrier['organization_id'] === $carrierOrganizationId;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{?string, ?string, ?string}
     */
    private function resolvePeriod(array $filters): array
    {
        $period = $filters['period'] ?? null;
        $dateFrom = $filters['service_date_from'] ?? null;
        $dateTo = $filters['service_date_to'] ?? null;

        if (! is_string($period) || $period === '') {
            return [
                is_string($dateFrom) || is_string($dateTo)
                    ? 'custom'
                    : null,
                is_string($dateFrom) ? $dateFrom : null,
                is_string($dateTo) ? $dateTo : null,
            ];
        }

        if ($period === 'custom') {
            return [
                $period,
                is_string($dateFrom) ? $dateFrom : null,
                is_string($dateTo) ? $dateTo : null,
            ];
        }

        if ($period === 'all_history') {
            return [$period, null, null];
        }

        $today = CarbonImmutable::today();
        [$resolvedFrom, $resolvedTo] = match ($period) {
            'current_month' => [$today->startOfMonth(), $today->endOfMonth()],
            'previous_month' => [
                $today->subMonthNoOverflow()->startOfMonth(),
                $today->subMonthNoOverflow()->endOfMonth(),
            ],
            'current_year' => [$today->startOfYear(), $today->endOfYear()],
            'previous_year' => [
                $today->subYear()->startOfYear(),
                $today->subYear()->endOfYear(),
            ],
            'last_12_months' => [
                $today->subMonths(11)->startOfMonth(),
                $today->endOfMonth(),
            ],
            default => [$today->startOfMonth(), $today->endOfMonth()],
        };

        return [
            $period,
            $resolvedFrom->format('Y-m-d'),
            $resolvedTo->format('Y-m-d'),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return list<array<string, int|string|null>>
     */
    private function quickPeriods(array $records): array
    {
        if ($records === []) {
            return [];
        }

        $today = CarbonImmutable::today();
        $bounds = $this->dateBounds($records);
        $allDateFrom = $bounds['date_from'] ?? '';
        $allDateTo = $bounds['date_to'] ?? '';

        /**
         * @var list<array{
         *     key:string,
         *     date_from:string,
         *     date_to:string
         * }> $definitions
         */
        $definitions = [
            [
                'key' => 'current_month',
                'date_from' => $today->startOfMonth()->format('Y-m-d'),
                'date_to' => $today->endOfMonth()->format('Y-m-d'),
            ],
            [
                'key' => 'previous_month',
                'date_from' => $today->subMonthNoOverflow()
                    ->startOfMonth()->format('Y-m-d'),
                'date_to' => $today->subMonthNoOverflow()
                    ->endOfMonth()->format('Y-m-d'),
            ],
            [
                'key' => 'current_year',
                'date_from' => $today->startOfYear()->format('Y-m-d'),
                'date_to' => $today->endOfYear()->format('Y-m-d'),
            ],
            [
                'key' => 'previous_year',
                'date_from' => $today->subYear()
                    ->startOfYear()->format('Y-m-d'),
                'date_to' => $today->subYear()
                    ->endOfYear()->format('Y-m-d'),
            ],
            [
                'key' => 'last_12_months',
                'date_from' => $today->subMonths(11)
                    ->startOfMonth()->format('Y-m-d'),
                'date_to' => $today->endOfMonth()->format('Y-m-d'),
            ],
            [
                'key' => 'all_history',
                'date_from' => $allDateFrom,
                'date_to' => $allDateTo,
            ],
        ];

        $result = [];

        foreach ($definitions as $definition) {
            $count = 0;

            foreach ($records as $record) {
                $serviceDate = (string) $record['service_date'];

                if (
                    $serviceDate >= $definition['date_from']
                    && $serviceDate <= $definition['date_to']
                ) {
                    $count++;
                }
            }

            if ($count > 0) {
                $result[] = [
                    ...$definition,
                    'route_count' => $count,
                ];
            }
        }

        return $result;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return array{date_from:?string, date_to:?string}
     */
    private function dateBounds(array $records): array
    {
        $dates = array_map(
            static fn (array $record): string => (string) $record['service_date'],
            $records,
        );

        if ($dates === []) {
            return ['date_from' => null, 'date_to' => null];
        }

        sort($dates, SORT_STRING);

        return [
            'date_from' => $dates[0],
            'date_to' => $dates[count($dates) - 1],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return list<array<string, int|string|bool|null>>
     */
    private function driverOptions(array $records): array
    {
        /** @var array<int, array<string, int|string|bool|null>> $options */
        $options = [];

        foreach ($records as $record) {
            /** @var DailyReport $report */
            $report = $record['report'];
            $driverId = (int) $record['driver_id'];

            if (! array_key_exists($driverId, $options)) {
                $driver = $report->getRelation('performedByDriver');

                $options[$driverId] = [
                    ...$this->driverIdentity(
                        $driverId,
                        $driver instanceof Driver ? $driver : null,
                    ),
                    'route_count' => 0,
                ];
            }

            $options[$driverId]['route_count'] =
                (int) $options[$driverId]['route_count'] + 1;
        }

        $rows = array_values($options);

        usort(
            $rows,
            static fn (array $left, array $right): int => strnatcasecmp(
                (string) ($left['name'] ?? ''),
                (string) ($right['name'] ?? ''),
            ),
        );

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return list<array<string, int|string|null>>
     */
    private function carrierOptions(array $records): array
    {
        /** @var array<string, array<string, int|string|null>> $options */
        $options = [];

        foreach ($records as $record) {
            /** @var array<string, int|string|null> $carrier */
            $carrier = $record['carrier'];
            $key = (string) $carrier['key'];

            if (! array_key_exists($key, $options)) {
                $options[$key] = [...$carrier, 'route_count' => 0];
            }

            $options[$key]['route_count'] =
                (int) $options[$key]['route_count'] + 1;
        }

        $rows = array_values($options);

        usort(
            $rows,
            static fn (array $left, array $right): int => strnatcasecmp(
                (string) $left['name'],
                (string) $right['name'],
            ),
        );

        return $rows;
    }

    /** @return array{?int, ?int, ?int, ?int, mixed, mixed} */
    private function values(DailyReport $report): array
    {
        return [
            $this->nullableInteger($report->getAttribute('loaded_parcels')),
            $this->nullableInteger($report->getAttribute('delivered_parcels')),
            $this->nullableInteger($report->getAttribute('redirected_parcels')),
            $this->nullableInteger($report->getAttribute('undelivered_parcels')),
            $report->getAttribute('planned_km'),
            $report->getAttribute('actual_km'),
        ];
    }

    private function serviceDate(DailyReport $report): string
    {
        return $this->dateValue($report->getAttribute('service_date'));
    }

    private function dateValue(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return CarbonImmutable::parse((string) $value)->format('Y-m-d');
    }

    private function nullableDateValue(mixed $value): ?string
    {
        return $value === null ? null : $this->dateValue($value);
    }

    /** @return array<string, int|string|bool|null> */
    private function driverIdentity(int $driverId, ?Driver $driver): array
    {
        $externalId = $driver?->getAttribute('external_driver_id');

        return [
            'id' => $driverId,
            'external_id' => $externalId === null
                ? null
                : (string) $externalId,
            'name' => $driver === null
                ? null
                : trim(
                    (string) $driver->getAttribute('first_name')
                    .' '.
                    (string) $driver->getAttribute('last_name'),
                ),
            'active' => $driver === null
                ? null
                : (bool) $driver->getAttribute('active'),
        ];
    }

    private function nullableInteger(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
