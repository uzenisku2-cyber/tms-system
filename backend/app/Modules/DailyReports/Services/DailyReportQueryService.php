<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Core\Organizations\OrganizationContext;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Models\DailyReportEvent;
use App\Modules\DailyReports\Models\DailyReportVersion;
use App\Modules\Drivers\Models\Driver;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class DailyReportQueryService
{
    /** @var array<string, list<string>> */
    private const STATUS_GROUPS = [
        'written' => [
            DailyReport::STATUS_DRAFT,
        ],
        'waiting' => [
            DailyReport::STATUS_SUBMITTED,
            DailyReport::STATUS_UNDER_REVIEW,
        ],
        'correction' => [
            DailyReport::STATUS_CORRECTION_REQUESTED,
        ],
        'corrected' => [
            DailyReport::STATUS_CORRECTED,
        ],
        'approved' => [
            DailyReport::STATUS_APPROVED,
        ],
        'closed' => [
            DailyReport::STATUS_CLOSED,
        ],
    ];

    public function __construct(
        private readonly OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, DailyReport>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = DailyReport::query()
            ->with('performedByDriver')
            ->forOrganization(
                $this->organizationContext->requireId(),
            );

        $status = $filters['status'] ?? null;

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $statusGroup = $filters['status_group'] ?? null;

        if (
            is_string($statusGroup)
            && array_key_exists(
                $statusGroup,
                self::STATUS_GROUPS,
            )
        ) {
            $query->whereIn(
                'status',
                self::STATUS_GROUPS[$statusGroup],
            );
        }

        $driverId = $filters['performed_by_driver_id'] ?? null;

        if (is_int($driverId) && $driverId > 0) {
            $query->where(
                'performed_by_driver_id',
                $driverId,
            );
        }

        $dateFrom = $filters['service_date_from'] ?? null;

        if (is_string($dateFrom) && $dateFrom !== '') {
            $query->where(
                'service_date',
                '>=',
                $dateFrom,
            );
        }

        $dateTo = $filters['service_date_to'] ?? null;

        if (is_string($dateTo) && $dateTo !== '') {
            $query->where(
                'service_date',
                '<=',
                $dateTo,
            );
        }

        $routeNumber = $filters['route_number'] ?? null;

        if (is_string($routeNumber)) {
            $normalizedRouteNumber = mb_strtolower(
                trim($routeNumber),
                'UTF-8',
            );

            if ($normalizedRouteNumber !== '') {
                $query->where(
                    'route_number_normalized',
                    'like',
                    '%'.$normalizedRouteNumber.'%',
                );
            }
        }

        $allowedSorts = [
            'service_date',
            'route_number',
            'status',
            'created_at',
        ];

        $requestedSort = $filters['sort_by'] ?? null;

        $sortBy = is_string($requestedSort)
            && in_array($requestedSort, $allowedSorts, true)
                ? $requestedSort
                : 'service_date';

        $sortDirection =
            ($filters['sort_dir'] ?? null) === 'asc'
                ? 'asc'
                : 'desc';

        $requestedPerPage = $filters['per_page'] ?? null;

        $perPage = is_int($requestedPerPage)
            && $requestedPerPage >= 1
            && $requestedPerPage <= 100
                ? $requestedPerPage
                : 25;

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, DailyReportVersion>
     */
    public function versions(string $publicId): Collection
    {
        /** @var Collection<int, DailyReportVersion> $versions */
        $versions = $this->findByPublicId($publicId)
            ->versions()
            ->orderByDesc('version_number')
            ->get();

        return $versions;
    }

    /**
     * @return Collection<int, DailyReportEvent>
     */
    public function events(string $publicId): Collection
    {
        /** @var Collection<int, DailyReportEvent> $events */
        $events = $this->findByPublicId($publicId)
            ->events()
            ->orderByDesc('id')
            ->get();

        return $events;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function navigation(array $filters): array
    {
        $baseQuery = $this->navigationBaseQuery(
            $filters,
        );

        /** @var array<int, int> $yearCounts */
        $yearCounts = [];

        /** @var array<string, array{year:int,month:int,total:int}> $monthCounts */
        $monthCounts = [];

        $dateRows = (clone $baseQuery)
            ->select('service_date')
            ->orderByDesc('service_date')
            ->get();

        foreach ($dateRows as $dateRow) {
            $rawDate = $dateRow->getAttribute(
                'service_date',
            );

            if ($rawDate === null) {
                continue;
            }

            $date = CarbonImmutable::parse(
                (string) $rawDate,
            );

            $year = (int) $date->format('Y');
            $month = (int) $date->format('n');
            $monthKey = $date->format('Y-m');

            $yearCounts[$year] =
                ($yearCounts[$year] ?? 0) + 1;

            if (! array_key_exists(
                $monthKey,
                $monthCounts,
            )) {
                $monthCounts[$monthKey] = [
                    'year' => $year,
                    'month' => $month,
                    'total' => 0,
                ];
            }

            $monthCounts[$monthKey]['total']++;
        }

        krsort($yearCounts, SORT_NUMERIC);
        krsort($monthCounts, SORT_STRING);

        $years = [];

        foreach ($yearCounts as $year => $total) {
            $years[] = [
                'year' => (int) $year,
                'total' => $total,
            ];
        }

        $months = [];

        foreach ($monthCounts as $key => $month) {
            $months[] = [
                'key' => $key,
                'year' => $month['year'],
                'month' => $month['month'],
                'total' => $month['total'],
            ];
        }

        $periodQuery = clone $baseQuery;
        $this->applyNavigationDateFilters(
            $periodQuery,
            $filters,
        );

        $statusCounts = [];

        $statusRows = $periodQuery
            ->selectRaw(
                'status, COUNT(*) AS aggregate',
            )
            ->groupBy('status')
            ->get();

        foreach ($statusRows as $statusRow) {
            $status = $statusRow->getAttribute(
                'status',
            );

            if (! is_string($status)) {
                continue;
            }

            $statusCounts[$status] = (int)
                $statusRow->getAttribute(
                    'aggregate',
                );
        }

        /*
         * Driver selector metadata is deliberately independent
         * of the currently selected driver. This keeps every
         * driver with route history selectable while the route
         * query itself remains server-side filtered.
         */
        $driverRouteRows = DailyReport::query()
            ->forOrganization(
                $this->organizationContext->requireId(),
            )
            ->selectRaw(
                'performed_by_driver_id, COUNT(*) AS aggregate, MAX(service_date) AS last_service_date',
            )
            ->groupBy('performed_by_driver_id')
            ->get();

        /** @var array<int, int> $driverRouteCounts */
        $driverRouteCounts = [];

        /** @var array<int, string|null> $driverLastRouteDates */
        $driverLastRouteDates = [];

        foreach ($driverRouteRows as $driverRouteRow) {
            $driverId = (int)
                $driverRouteRow->getAttribute(
                    'performed_by_driver_id',
                );

            if ($driverId <= 0) {
                continue;
            }

            $driverRouteCounts[$driverId] = (int)
                $driverRouteRow->getAttribute(
                    'aggregate',
                );
            $lastServiceDate =
                $driverRouteRow->getAttribute(
                    'last_service_date',
                );

            $driverLastRouteDates[$driverId] =
                is_string($lastServiceDate)
                    ? $lastServiceDate
                    : (
                        $lastServiceDate !== null
                            ? (string) $lastServiceDate
                            : null
                    );
        }

        $driverOptions = [];

        if ($driverRouteCounts !== []) {
            $driversById = Driver::query()
                ->whereKey(
                    array_keys($driverRouteCounts),
                )
                ->get()
                ->keyBy(
                    static fn (Driver $driver): int => (int) $driver->getKey(),
                );

            foreach ($driverRouteCounts as $driverId => $total) {
                /** @var Driver|null $driver */
                $driver = $driversById->get(
                    $driverId,
                );

                if (! $driver instanceof Driver) {
                    continue;
                }

                $name = trim(
                    (string) $driver->getAttribute('last_name')
                    .' '.
                    (string) $driver->getAttribute('first_name'),
                );

                if ($name === '') {
                    $externalDriverId =
                        $driver->getAttribute(
                            'external_driver_id',
                        );

                    $name = is_string($externalDriverId)
                        && $externalDriverId !== ''
                            ? $externalDriverId
                            : 'ĹidiÄŤ '.$driverId;
                }

                $driverOptions[] = [
                    'id' => $driverId,
                    'name' => $name,
                    'external_driver_id' => $driver->getAttribute(
                        'external_driver_id',
                    ),
                    'total' => $total,
                    'last_service_date' => $driverLastRouteDates[$driverId]
                        ?? null,
                    'active' => (bool) $driver->getAttribute(
                        'active',
                    ),
                ];
            }

            usort(
                $driverOptions,
                static fn (
                    array $left,
                    array $right,
                ): int => strnatcasecmp(
                    (string) $left['name'],
                    (string) $right['name'],
                ),
            );
        }
        $timezone = config('app.timezone');

        if (! is_string($timezone) || $timezone === '') {
            $timezone = 'UTC';
        }

        $today = CarbonImmutable::now(
            $timezone,
        )->startOfDay();

        $yesterday = $today->subDay();
        $lastSevenFrom = $today->subDays(6);
        $currentMonthFrom = $today->startOfMonth();
        $currentMonthTo = $today->endOfMonth();
        $previousMonthFrom =
            $today->subMonthNoOverflow()
                ->startOfMonth();
        $previousMonthTo =
            $today->subMonthNoOverflow()
                ->endOfMonth();
        $currentYearFrom = $today->startOfYear();
        $currentYearTo = $today->endOfYear();

        return [
            'today' => $today->format('Y-m-d'),
            'years' => $years,
            'months' => $months,
            'drivers' => $driverOptions,
            'status_counts' => $statusCounts,
            'quick_periods' => [
                'yesterday' => $this->quickPeriod(
                    clone $baseQuery,
                    "V\u{010D}era",
                    $yesterday,
                    $yesterday,
                ),
                'last_7_days' => $this->quickPeriod(
                    clone $baseQuery,
                    'Posledních 7 dní',
                    $lastSevenFrom,
                    $today,
                ),
                'current_month' => $this->quickPeriod(
                    clone $baseQuery,
                    'Tento měsíc',
                    $currentMonthFrom,
                    $currentMonthTo,
                ),
                'previous_month' => $this->quickPeriod(
                    clone $baseQuery,
                    'Minulý měsíc',
                    $previousMonthFrom,
                    $previousMonthTo,
                ),
                'current_year' => $this->quickPeriod(
                    clone $baseQuery,
                    'Tento rok',
                    $currentYearFrom,
                    $currentYearTo,
                ),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<DailyReport>
     */
    private function navigationBaseQuery(
        array $filters,
    ): Builder {
        $query = DailyReport::query()
            ->forOrganization(
                $this->organizationContext->requireId(),
            );

        $driverId =
            $filters['performed_by_driver_id']
                ?? null;

        if (is_int($driverId) && $driverId > 0) {
            $query->where(
                'performed_by_driver_id',
                $driverId,
            );
        }

        $routeNumber =
            $filters['route_number']
                ?? null;

        if (is_string($routeNumber)) {
            $normalizedRouteNumber =
                mb_strtolower(
                    trim($routeNumber),
                    'UTF-8',
                );

            if ($normalizedRouteNumber !== '') {
                $query->where(
                    'route_number_normalized',
                    'like',
                    '%'.$normalizedRouteNumber.'%',
                );
            }
        }

        return $query;
    }

    /**
     * @param  Builder<DailyReport>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyNavigationDateFilters(
        Builder $query,
        array $filters,
    ): void {
        $dateFrom =
            $filters['service_date_from']
                ?? null;

        if (
            is_string($dateFrom)
            && $dateFrom !== ''
        ) {
            $query->where(
                'service_date',
                '>=',
                $dateFrom,
            );
        }

        $dateTo =
            $filters['service_date_to']
                ?? null;

        if (
            is_string($dateTo)
            && $dateTo !== ''
        ) {
            $query->where(
                'service_date',
                '<=',
                $dateTo,
            );
        }
    }

    /**
     * @param  Builder<DailyReport>  $query
     * @return array{label:string,from:string,to:string,total:int}
     */
    private function quickPeriod(
        Builder $query,
        string $label,
        CarbonImmutable $from,
        CarbonImmutable $to,
    ): array {
        $fromDate = $from->format('Y-m-d');
        $toDate = $to->format('Y-m-d');

        $total = $query
            ->whereBetween(
                'service_date',
                [
                    $fromDate,
                    $toDate,
                ],
            )
            ->count();

        return [
            'label' => $label,
            'from' => $fromDate,
            'to' => $toDate,
            'total' => $total,
        ];
    }

    public function findByPublicId(string $publicId): DailyReport
    {
        return DailyReport::query()
            ->with('performedByDriver')
            ->forOrganization(
                $this->organizationContext->requireId(),
            )
            ->where('public_id', $publicId)
            ->firstOrFail();
    }
}
