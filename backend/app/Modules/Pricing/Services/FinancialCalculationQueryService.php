<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Organizations\OrganizationContext;
use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Models\FinancialCalculationEvent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class FinancialCalculationQueryService
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, FinancialCalculation>
     */
    public function paginate(
        array $filters,
    ): LengthAwarePaginator {
        $query = $this->visibleQuery()
            ->with([
                'priceList:id,public_id',
                'priceListVersion:id,version_number',
                'dailyReport:id,public_id',
                'supersedesCalculation:id,public_id',
            ]);

        $status = $filters['status'] ?? null;

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $currency = $filters['currency'] ?? null;

        if (is_string($currency) && $currency !== '') {
            $query->where('currency', $currency);
        }

        $allowedSorts = [
            'calculated_at',
            'status',
            'currency',
            'total_amount',
            'created_at',
        ];

        $requestedSort = $filters['sort_by'] ?? null;

        $sortBy = is_string($requestedSort)
            && in_array(
                $requestedSort,
                $allowedSorts,
                true,
            )
                ? $requestedSort
                : 'calculated_at';

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
            ->orderBy('id', $sortDirection)
            ->paginate($perPage);
    }

    public function findByPublicId(
        string $publicId,
    ): FinancialCalculation {
        return $this->visibleQuery()
            ->with([
                'priceList:id,public_id',
                'priceListVersion:id,version_number',
                'dailyReport:id,public_id',
                'supersedesCalculation:id,public_id',
                'lines',
            ])
            ->where('public_id', $publicId)
            ->firstOrFail();
    }

    /**
     * @return Collection<int, FinancialCalculationEvent>
     */
    public function events(
        string $publicId,
    ): Collection {
        $calculation = $this->visibleQuery()
            ->where('public_id', $publicId)
            ->firstOrFail();

        /** @var Collection<int, FinancialCalculationEvent> $events */
        $events = $calculation
            ->events()
            ->get();

        return $events;
    }

    /**
     * A persisted calculation is historical financial evidence.
     *
     * Visibility therefore follows the two parties of the stored direct
     * commercial relationship and does not depend on the relationship's
     * current status or current validity period.
     *
     * @return Builder<FinancialCalculation>
     */
    private function visibleQuery(): Builder
    {
        $organizationId =
            $this->organizationContext->requireId();

        return FinancialCalculation::query()
            ->whereHas(
                'organizationRelationship',
                function (
                    Builder $relationshipQuery,
                ) use (
                    $organizationId,
                ): void {
                    $relationshipQuery->where(
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
                    );
                },
            );
    }
}
