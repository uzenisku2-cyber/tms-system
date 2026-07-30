<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Organizations\OrganizationContext;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListVersion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class PriceListQueryService
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, PriceList>
     */
    public function paginate(
        array $filters,
    ): LengthAwarePaginator {
        $query = $this->visibleQuery();

        $status = $filters['status'] ?? null;

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $currency = $filters['currency'] ?? null;

        if (is_string($currency) && $currency !== '') {
            $query->where('currency', $currency);
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
        string $publicId,
    ): PriceList {
        return $this->visibleQuery()
            ->where('public_id', $publicId)
            ->firstOrFail();
    }

    /**
     * @return Collection<int, PriceListVersion>
     */
    public function versions(
        string $publicId,
    ): Collection {
        /** @var Collection<int, PriceListVersion> $versions */
        $versions = $this->findByPublicId($publicId)
            ->versions()
            ->with('items')
            ->orderByDesc('version_number')
            ->get();

        return $versions;
    }

    public function findVersion(
        string $publicId,
        int $versionNumber,
    ): PriceListVersion {
        return $this->findByPublicId($publicId)
            ->versions()
            ->with('items')
            ->where(
                'version_number',
                $versionNumber,
            )
            ->firstOrFail();
    }

    /**
     * @return Builder<PriceList>
     */
    private function visibleQuery(): Builder
    {
        $organizationId =
            $this->organizationContext->requireId();

        $moment = now();

        return PriceList::query()
            ->forParticipatingOrganization(
                $organizationId,
            )
            ->whereHas(
                'organizationRelationship',
                function (
                    Builder $relationshipQuery,
                ) use (
                    $organizationId,
                    $moment,
                ): void {
                    $relationshipQuery
                        ->where(
                            'status',
                            OrganizationRelationship::STATUS_ACTIVE,
                        )
                        ->where(
                            function (
                                Builder $partyQuery,
                            ) use (
                                $organizationId,
                            ): void {
                                $partyQuery
                                    ->where(
                                        'source_organization_id',
                                        $organizationId,
                                    )
                                    ->orWhere(
                                        'target_organization_id',
                                        $organizationId,
                                    );
                            },
                        )
                        ->where(
                            function (
                                Builder $validFromQuery,
                            ) use (
                                $moment,
                            ): void {
                                $validFromQuery
                                    ->whereNull('valid_from')
                                    ->orWhere(
                                        'valid_from',
                                        '<=',
                                        $moment,
                                    );
                            },
                        )
                        ->where(
                            function (
                                Builder $validUntilQuery,
                            ) use (
                                $moment,
                            ): void {
                                $validUntilQuery
                                    ->whereNull('valid_until')
                                    ->orWhere(
                                        'valid_until',
                                        '>=',
                                        $moment,
                                    );
                            },
                        );
                },
            );
    }
}
