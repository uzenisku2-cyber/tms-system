<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\Pricing\Models\FinancialCalculation;
use DomainException;
use LogicException;

final class FinancialCalculationCurrentSourceResolver
{
    public function resolveUsableForDailyReport(
        int $organizationId,
        int $organizationRelationshipId,
        int $priceListVersionId,
        int $dailyReportId,
    ): FinancialCalculation {
        $this->assertPositiveIdentifier(
            $organizationId,
            'Organization identifier',
        );

        $this->assertPositiveIdentifier(
            $organizationRelationshipId,
            'Organization-relationship identifier',
        );

        $this->assertPositiveIdentifier(
            $priceListVersionId,
            'Price-list version identifier',
        );

        $this->assertPositiveIdentifier(
            $dailyReportId,
            'Daily-report identifier',
        );

        $currentLeaves = FinancialCalculation::query()
            ->where(
                'organization_id',
                $organizationId,
            )
            ->where(
                'organization_relationship_id',
                $organizationRelationshipId,
            )
            ->where(
                'price_list_version_id',
                $priceListVersionId,
            )
            ->where(
                'daily_report_id',
                $dailyReportId,
            )
            ->whereDoesntHave(
                'supersededByCalculations',
            )
            ->orderBy('id')
            ->get();

        if ($currentLeaves->isEmpty()) {
            throw new DomainException(
                (
                    'No current financial-calculation leaf exists for '
                    .'the requested route pricing context.'
                ),
            );
        }

        if ($currentLeaves->count() !== 1) {
            throw new LogicException(
                (
                    'The route pricing context contains multiple current '
                    .'financial-calculation leaves and requires repair.'
                ),
            );
        }

        $current = $currentLeaves->first();

        if (! $current instanceof FinancialCalculation) {
            throw new LogicException(
                'The current financial calculation could not be resolved.',
            );
        }

        if ($current->isApproved() || $current->isClosed()) {
            return $current;
        }

        if ($current->isCancelled()) {
            throw new DomainException(
                (
                    'The current financial calculation is cancelled and '
                    .'cannot be used as a conditional-pricing source.'
                ),
            );
        }

        throw new DomainException(
            (
                'The current financial calculation is not final; only '
                .'approved or closed calculations may be used as '
                .'conditional-pricing sources.'
            ),
        );
    }

    private function assertPositiveIdentifier(
        int $value,
        string $label,
    ): void {
        if ($value < 1) {
            throw new DomainException(
                $label.' must be positive.',
            );
        }
    }
}
