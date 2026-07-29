<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Core\Organizations\OrganizationContext;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Models\DailyReportEvent;
use App\Modules\DailyReports\Models\DailyReportVersion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class DailyReportQueryService
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, DailyReport>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = DailyReport::query()->forOrganization(
            $this->organizationContext->requireId(),
        );

        $status = $filters['status'] ?? null;

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
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

    public function findByPublicId(string $publicId): DailyReport
    {
        return DailyReport::query()
            ->forOrganization(
                $this->organizationContext->requireId(),
            )
            ->where('public_id', $publicId)
            ->firstOrFail();
    }
}
