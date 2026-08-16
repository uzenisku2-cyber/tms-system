<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Pricing\Models\DriverPriceList;
use App\Modules\Pricing\Models\DriverPriceListVersion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class DriverPriceListQueryService
{
    public const READ_PERMISSION = 'compensation.view';

    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly DriverSupervisoryAuthorizationService $authorization,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, DriverPriceList>
     */
    public function paginate(
        User $actor,
        array $filters,
    ): LengthAwarePaginator {
        $query = $this->visibleQuery($actor);

        $status = $filters['status'] ?? null;

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $currency = $filters['currency'] ?? null;

        if (is_string($currency) && $currency !== '') {
            $query->where('currency', $currency);
        }

        $assignmentId = $filters[
            'driver_organization_assignment_id'
        ] ?? null;

        if (is_int($assignmentId) && $assignmentId > 0) {
            $query->where(
                'driver_organization_assignment_id',
                $assignmentId,
            );
        }

        $allowedSorts = [
            'name',
            'status',
            'currency',
            'current_version',
            'created_at',
            'updated_at',
        ];

        $requestedSort = $filters['sort_by'] ?? null;

        $sortBy = is_string($requestedSort)
            && in_array(
                $requestedSort,
                $allowedSorts,
                true,
            )
                ? $requestedSort
                : 'name';

        $sortDirection =
            ($filters['sort_dir'] ?? null) === 'desc'
                ? 'desc'
                : 'asc';

        $requestedPerPage = $filters['per_page'] ?? null;

        $perPage = is_int($requestedPerPage)
            && $requestedPerPage >= 1
            && $requestedPerPage <= 100
                ? $requestedPerPage
                : 25;

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function findByPublicId(
        User $actor,
        string $publicId,
    ): DriverPriceList {
        return $this->visibleQuery($actor)
            ->where('public_id', $publicId)
            ->firstOrFail();
    }

    /**
     * @return Collection<int, DriverPriceListVersion>
     */
    public function versions(
        User $actor,
        string $publicId,
    ): Collection {
        /** @var Collection<int, DriverPriceListVersion> $versions */
        $versions = $this
            ->findByPublicId($actor, $publicId)
            ->versions()
            ->with('items')
            ->reorder('version_number', 'desc')
            ->get();

        return $versions;
    }

    public function findVersion(
        User $actor,
        string $publicId,
        int $versionNumber,
    ): DriverPriceListVersion {
        return $this
            ->findByPublicId($actor, $publicId)
            ->versions()
            ->with('items')
            ->where(
                'version_number',
                $versionNumber,
            )
            ->firstOrFail();
    }

    /**
     * @return Builder<DriverPriceList>
     */
    private function visibleQuery(User $actor): Builder
    {
        $organizationId =
            $this->organizationContext->requireId();

        $assignmentIds =
            $this->authorization
                ->visibleDriverOrganizationAssignmentIds(
                    actor: $actor,
                    organizationId: $organizationId,
                    requiredPermission: self::READ_PERMISSION,
                );

        return DriverPriceList::query()
            ->where(
                'managed_by_organization_id',
                $organizationId,
            )
            ->whereIn(
                'driver_organization_assignment_id',
                $assignmentIds,
            );
    }
}
