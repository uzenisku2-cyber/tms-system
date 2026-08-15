<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Organizations\OrganizationContext;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListConditionalRule;
use App\Modules\Pricing\Models\PriceListVersion;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DomainException;
use LogicException;

final class FinancialConditionalScopeSourceResolver
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly FinancialCalculationCurrentSourceResolver $currentSourceResolver,
    ) {}

    /**
     * @return array{
     *     organization_id:int,
     *     organization_relationship_id:int,
     *     price_list_id:int,
     *     price_list_version_id:int,
     *     performed_by_driver_id:int,
     *     period_start:string,
     *     period_end:string,
     *     daily_report_ids:list<int>,
     *     financial_calculation_ids:list<int>
     * }
     */
    public function resolveMonthlyDriverSources(
        int $conditionalRuleId,
        int $performedByDriverId,
        string $calendarMonth,
    ): array {
        $this->assertPositiveIdentifier(
            $conditionalRuleId,
            'Conditional-rule identifier',
        );

        $this->assertPositiveIdentifier(
            $performedByDriverId,
            'Performing-driver identifier',
        );

        $monthStart = $this->calendarMonthStart(
            $calendarMonth,
        );

        $organizationId = $this->organizationContext->requireId();

        $rule = PriceListConditionalRule::query()
            ->whereKey($conditionalRuleId)
            ->first();

        if (! $rule instanceof PriceListConditionalRule) {
            throw new DomainException(
                'The conditional pricing rule is unavailable.',
            );
        }

        if (
            $rule->getAttribute('evaluation_scope')
            !== PriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_DRIVER
        ) {
            throw new DomainException(
                'The conditional pricing rule is not monthly-driver scoped.',
            );
        }

        $priceListVersionId = $this->positiveInteger(
            $rule->getAttribute('price_list_version_id'),
            'Conditional-rule price-list version',
        );

        $priceListVersion = PriceListVersion::query()
            ->whereKey($priceListVersionId)
            ->first();

        if (! $priceListVersion instanceof PriceListVersion) {
            throw new DomainException(
                'The conditional-rule price-list version is unavailable.',
            );
        }

        if (
            ! $priceListVersion->isActive()
            && ! $priceListVersion->isReplaced()
            && ! $priceListVersion->isExpired()
        ) {
            throw new DomainException(
                'Monthly conditional scope requires an active, replaced or expired price-list version.',
            );
        }

        $priceListId = $this->positiveInteger(
            $priceListVersion->getAttribute('price_list_id'),
            'Price-list identifier',
        );

        $priceList = PriceList::query()
            ->whereKey($priceListId)
            ->first();

        if (! $priceList instanceof PriceList) {
            throw new DomainException(
                'The conditional-rule price list is unavailable.',
            );
        }

        $relationshipId = $this->positiveInteger(
            $priceList->getAttribute(
                'organization_relationship_id',
            ),
            'Price-list organization relationship',
        );

        if (
            $this->positiveInteger(
                $priceListVersion->getAttribute(
                    'organization_relationship_id',
                ),
                'Price-list version organization relationship',
            ) !== $relationshipId
        ) {
            throw new LogicException(
                'The price-list version relationship does not match its parent price list.',
            );
        }

        $customerOrganizationId = $this->positiveInteger(
            $priceList->getAttribute(
                'customer_organization_id',
            ),
            'Price-list customer organization',
        );

        $providerOrganizationId = $this->positiveInteger(
            $priceList->getAttribute(
                'provider_organization_id',
            ),
            'Price-list provider organization',
        );

        $ownerOrganizationId = $this->positiveInteger(
            $priceList->getAttribute(
                'owner_organization_id',
            ),
            'Price-list owner organization',
        );

        if ($ownerOrganizationId !== $customerOrganizationId) {
            throw new LogicException(
                'The price-list owner must match its customer organization.',
            );
        }

        if ($providerOrganizationId !== $organizationId) {
            throw new DomainException(
                'The conditional pricing scope is outside the active provider organization.',
            );
        }

        if ($customerOrganizationId === $providerOrganizationId) {
            throw new DomainException(
                'Monthly subcontracting scope cannot use an internal same-organization price list.',
            );
        }

        $relationship = OrganizationRelationship::query()
            ->whereKey($relationshipId)
            ->first();

        if (! $relationship instanceof OrganizationRelationship) {
            throw new DomainException(
                'The price-list commercial relationship is unavailable.',
            );
        }

        if (
            $relationship->getAttribute('relationship_type')
            !== OrganizationRelationship::TYPE_SUBCONTRACTING
        ) {
            throw new DomainException(
                'Monthly conditional scope requires a subcontracting relationship.',
            );
        }

        if (
            $this->positiveInteger(
                $relationship->getAttribute(
                    'source_organization_id',
                ),
                'Relationship source organization',
            ) !== $customerOrganizationId
            || $this->positiveInteger(
                $relationship->getAttribute(
                    'target_organization_id',
                ),
                'Relationship target organization',
            ) !== $providerOrganizationId
        ) {
            throw new LogicException(
                'The price-list parties do not match the commercial relationship.',
            );
        }

        [
            $periodStart,
            $periodEnd,
        ] = $this->monthlyVersionPeriod(
            $monthStart,
            $priceListVersion,
        );

        $this->assertRelationshipCoversPeriod(
            $relationship,
            $periodStart,
            $periodEnd,
        );

        $reports = DailyReport::query()
            ->where(
                'organization_id',
                $customerOrganizationId,
            )
            ->where(
                'performed_by_driver_id',
                $performedByDriverId,
            )
            ->whereNull('deleted_at')
            ->whereDate(
                'service_date',
                '>=',
                $periodStart,
            )
            ->whereDate(
                'service_date',
                '<=',
                $periodEnd,
            )
            ->orderBy('service_date')
            ->orderBy('id')
            ->get([
                'id',
                'service_date',
                'current_version',
            ]);

        if ($reports->isEmpty()) {
            throw new DomainException(
                'No daily reports exist in the monthly conditional scope period.',
            );
        }

        $dailyReportIds = [];
        $financialCalculationIds = [];

        foreach ($reports as $report) {
            if (! $report instanceof DailyReport) {
                throw new LogicException(
                    'The monthly conditional scope contains an invalid daily report.',
                );
            }

            $dailyReportId = $this->positiveInteger(
                $report->getKey(),
                'Daily-report identifier',
            );

            $serviceDateValue = $report->getAttribute(
                'service_date',
            );

            if (! $serviceDateValue instanceof DateTimeInterface) {
                throw new LogicException(
                    'The daily report has an invalid service date.',
                );
            }

            $serviceDate = CarbonImmutable::instance(
                $serviceDateValue,
            )->format('Y-m-d');

            $assignments = DriverOrganizationAssignment::query()
                ->where(
                    'driver_id',
                    $performedByDriverId,
                )
                ->whereDate(
                    'valid_from',
                    '<=',
                    $serviceDate,
                )
                ->where(
                    static function ($query) use ($serviceDate): void {
                        $query
                            ->whereNull('valid_until')
                            ->orWhereDate(
                                'valid_until',
                                '>=',
                                $serviceDate,
                            );
                    },
                )
                ->orderBy('id')
                ->get([
                    'id',
                    'organization_id',
                ]);

            if ($assignments->count() !== 1) {
                throw new DomainException(
                    'Every daily report in a monthly conditional scope must have exactly one effective driver organization assignment.',
                );
            }

            $assignment = $assignments->first();

            if (! $assignment instanceof DriverOrganizationAssignment) {
                throw new LogicException(
                    'The effective driver organization assignment could not be resolved.',
                );
            }

            $assignedOrganizationId = $this->positiveInteger(
                $assignment->getAttribute('organization_id'),
                'Assigned driver organization',
            );

            if ($assignedOrganizationId !== $providerOrganizationId) {
                continue;
            }

            $source = $this->currentSourceResolver
                ->resolveUsableForDailyReport(
                    organizationId: $providerOrganizationId,
                    organizationRelationshipId: $relationshipId,
                    priceListVersionId: $priceListVersionId,
                    dailyReportId: $dailyReportId,
                );

            $currentDailyReportVersion = $this->positiveInteger(
                $report->getAttribute('current_version'),
                'Current daily-report version',
            );

            $sourceDailyReportVersion = $this->positiveInteger(
                $source->getAttribute('daily_report_version'),
                'Source daily-report version',
            );

            if (
                $sourceDailyReportVersion
                !== $currentDailyReportVersion
            ) {
                throw new DomainException(
                    'The current financial calculation is stale against the current daily-report version.',
                );
            }

            $dailyReportIds[] = $dailyReportId;
            $financialCalculationIds[] = $this->positiveInteger(
                $source->getKey(),
                'Financial-calculation identifier',
            );
        }

        if ($dailyReportIds === []) {
            throw new DomainException(
                'No provider-affiliated daily reports exist in the monthly conditional scope.',
            );
        }

        return [
            'organization_id' => $providerOrganizationId,
            'organization_relationship_id' => $relationshipId,
            'price_list_id' => $priceListId,
            'price_list_version_id' => $priceListVersionId,
            'performed_by_driver_id' => $performedByDriverId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'daily_report_ids' => $dailyReportIds,
            'financial_calculation_ids' => $financialCalculationIds,
        ];
    }

    /**
     * @return array{
     *     organization_id:int,
     *     organization_relationship_id:int,
     *     price_list_id:int,
     *     price_list_version_id:int,
     *     performed_by_driver_id:int,
     *     period_start:string,
     *     period_end:string,
     *     daily_report_ids:list<int>,
     *     financial_calculation_ids:list<int>
     * }
     */
    public function resolvePerRouteSources(
        int $conditionalRuleId,
        int $dailyReportId,
    ): array {
        $this->assertPositiveIdentifier(
            $conditionalRuleId,
            'Conditional-rule identifier',
        );

        $this->assertPositiveIdentifier(
            $dailyReportId,
            'Daily-report identifier',
        );

        $organizationId = $this->organizationContext->requireId();

        $rule = PriceListConditionalRule::query()
            ->whereKey($conditionalRuleId)
            ->first();

        if (! $rule instanceof PriceListConditionalRule) {
            throw new DomainException(
                'The conditional pricing rule is unavailable.',
            );
        }

        if (
            $rule->getAttribute('evaluation_scope')
            !== PriceListConditionalRule::EVALUATION_SCOPE_PER_ROUTE
        ) {
            throw new DomainException(
                'The conditional pricing rule is not per-route scoped.',
            );
        }

        $priceListVersionId = $this->positiveInteger(
            $rule->getAttribute('price_list_version_id'),
            'Conditional-rule price-list version',
        );

        $priceListVersion = PriceListVersion::query()
            ->whereKey($priceListVersionId)
            ->first();

        if (! $priceListVersion instanceof PriceListVersion) {
            throw new DomainException(
                'The conditional-rule price-list version is unavailable.',
            );
        }

        if (
            ! $priceListVersion->isActive()
            && ! $priceListVersion->isReplaced()
            && ! $priceListVersion->isExpired()
        ) {
            throw new DomainException(
                'Per-route conditional scope requires an active, replaced or expired price-list version.',
            );
        }

        $priceListId = $this->positiveInteger(
            $priceListVersion->getAttribute('price_list_id'),
            'Price-list identifier',
        );

        $priceList = PriceList::query()
            ->whereKey($priceListId)
            ->first();

        if (! $priceList instanceof PriceList) {
            throw new DomainException(
                'The conditional-rule price list is unavailable.',
            );
        }

        $relationshipId = $this->positiveInteger(
            $priceList->getAttribute(
                'organization_relationship_id',
            ),
            'Price-list organization relationship',
        );

        if (
            $this->positiveInteger(
                $priceListVersion->getAttribute(
                    'organization_relationship_id',
                ),
                'Price-list version organization relationship',
            ) !== $relationshipId
        ) {
            throw new LogicException(
                'The price-list version relationship does not match its parent price list.',
            );
        }

        $customerOrganizationId = $this->positiveInteger(
            $priceList->getAttribute(
                'customer_organization_id',
            ),
            'Price-list customer organization',
        );

        $providerOrganizationId = $this->positiveInteger(
            $priceList->getAttribute(
                'provider_organization_id',
            ),
            'Price-list provider organization',
        );

        $ownerOrganizationId = $this->positiveInteger(
            $priceList->getAttribute(
                'owner_organization_id',
            ),
            'Price-list owner organization',
        );

        if ($ownerOrganizationId !== $customerOrganizationId) {
            throw new LogicException(
                'The price-list owner must match its customer organization.',
            );
        }

        if ($providerOrganizationId !== $organizationId) {
            throw new DomainException(
                'The conditional pricing scope is outside the active provider organization.',
            );
        }

        if ($customerOrganizationId === $providerOrganizationId) {
            throw new DomainException(
                'Per-route subcontracting scope cannot use an internal same-organization price list.',
            );
        }

        $relationship = OrganizationRelationship::query()
            ->whereKey($relationshipId)
            ->first();

        if (! $relationship instanceof OrganizationRelationship) {
            throw new DomainException(
                'The price-list commercial relationship is unavailable.',
            );
        }

        if (
            $relationship->getAttribute('relationship_type')
            !== OrganizationRelationship::TYPE_SUBCONTRACTING
        ) {
            throw new DomainException(
                'Per-route conditional scope requires a subcontracting relationship.',
            );
        }

        if (
            $this->positiveInteger(
                $relationship->getAttribute(
                    'source_organization_id',
                ),
                'Relationship source organization',
            ) !== $customerOrganizationId
            || $this->positiveInteger(
                $relationship->getAttribute(
                    'target_organization_id',
                ),
                'Relationship target organization',
            ) !== $providerOrganizationId
        ) {
            throw new LogicException(
                'The price-list parties do not match the commercial relationship.',
            );
        }

        $report = DailyReport::query()
            ->whereKey($dailyReportId)
            ->where(
                'organization_id',
                $customerOrganizationId,
            )
            ->whereNull('deleted_at')
            ->first([
                'id',
                'performed_by_driver_id',
                'service_date',
                'current_version',
            ]);

        if (! $report instanceof DailyReport) {
            throw new DomainException(
                'The daily report is unavailable in the per-route conditional scope.',
            );
        }

        $performedByDriverId = $this->positiveInteger(
            $report->getAttribute('performed_by_driver_id'),
            'Performing-driver identifier',
        );

        $serviceDateValue = $report->getAttribute(
            'service_date',
        );

        if (! $serviceDateValue instanceof DateTimeInterface) {
            throw new LogicException(
                'The daily report has an invalid service date.',
            );
        }

        $serviceDate = CarbonImmutable::instance(
            $serviceDateValue,
        )->format('Y-m-d');

        [
            $periodStart,
            $periodEnd,
        ] = $this->perRouteVersionPeriod(
            $serviceDate,
            $priceListVersion,
        );

        $this->assertRelationshipCoversServiceDate(
            $relationship,
            $serviceDate,
        );

        $assignments = DriverOrganizationAssignment::query()
            ->where(
                'driver_id',
                $performedByDriverId,
            )
            ->whereDate(
                'valid_from',
                '<=',
                $serviceDate,
            )
            ->where(
                static function ($query) use ($serviceDate): void {
                    $query
                        ->whereNull('valid_until')
                        ->orWhereDate(
                            'valid_until',
                            '>=',
                            $serviceDate,
                        );
                },
            )
            ->orderBy('id')
            ->get([
                'id',
                'organization_id',
            ]);

        if ($assignments->count() !== 1) {
            throw new DomainException(
                'A per-route conditional scope must have exactly one effective driver organization assignment.',
            );
        }

        $assignment = $assignments->first();

        if (! $assignment instanceof DriverOrganizationAssignment) {
            throw new LogicException(
                'The effective driver organization assignment could not be resolved.',
            );
        }

        $assignedOrganizationId = $this->positiveInteger(
            $assignment->getAttribute('organization_id'),
            'Assigned driver organization',
        );

        if ($assignedOrganizationId !== $providerOrganizationId) {
            throw new DomainException(
                'The route performing driver is outside the active provider organization.',
            );
        }

        $source = $this->currentSourceResolver
            ->resolveUsableForDailyReport(
                organizationId: $providerOrganizationId,
                organizationRelationshipId: $relationshipId,
                priceListVersionId: $priceListVersionId,
                dailyReportId: $dailyReportId,
            );

        $currentDailyReportVersion = $this->positiveInteger(
            $report->getAttribute('current_version'),
            'Current daily-report version',
        );

        $sourceDailyReportVersion = $this->positiveInteger(
            $source->getAttribute('daily_report_version'),
            'Source daily-report version',
        );

        if (
            $sourceDailyReportVersion
            !== $currentDailyReportVersion
        ) {
            throw new DomainException(
                'The current financial calculation is stale against the current daily-report version.',
            );
        }

        return [
            'organization_id' => $providerOrganizationId,
            'organization_relationship_id' => $relationshipId,
            'price_list_id' => $priceListId,
            'price_list_version_id' => $priceListVersionId,
            'performed_by_driver_id' => $performedByDriverId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'daily_report_ids' => [$dailyReportId],
            'financial_calculation_ids' => [
                $this->positiveInteger(
                    $source->getKey(),
                    'Financial-calculation identifier',
                ),
            ],
        ];
    }

    private function calendarMonthStart(
        string $calendarMonth,
    ): CarbonImmutable {
        if (
            preg_match('/^\d{4}-\d{2}$/D', $calendarMonth)
            !== 1
        ) {
            throw new DomainException(
                'Monthly conditional scope requires a YYYY-MM calendar month.',
            );
        }

        $monthStart = CarbonImmutable::parse(
            $calendarMonth.'-01',
        )->startOfMonth();

        if ($monthStart->format('Y-m') !== $calendarMonth) {
            throw new DomainException(
                'The monthly conditional scope calendar month is invalid.',
            );
        }

        return $monthStart;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function perRouteVersionPeriod(
        string $serviceDate,
        PriceListVersion $priceListVersion,
    ): array {
        $date = CarbonImmutable::parse(
            $serviceDate,
        )->startOfDay();

        $validFrom = $priceListVersion->getAttribute(
            'valid_from',
        );

        if (! $validFrom instanceof DateTimeInterface) {
            throw new LogicException(
                'An applicable price-list version must have a valid start date.',
            );
        }

        $versionStart = CarbonImmutable::instance(
            $validFrom,
        )->startOfDay();

        if ($date->isBefore($versionStart)) {
            throw new DomainException(
                'The price-list version does not cover the requested service date.',
            );
        }

        $validUntil = $priceListVersion->getAttribute(
            'valid_until',
        );

        if ($validUntil !== null) {
            if (! $validUntil instanceof DateTimeInterface) {
                throw new LogicException(
                    'The price-list version has an invalid end date.',
                );
            }

            $versionEnd = CarbonImmutable::instance(
                $validUntil,
            )->startOfDay();

            if ($versionEnd->isBefore($versionStart)) {
                throw new LogicException(
                    'The price-list version has an invalid effective period.',
                );
            }

            if ($date->isAfter($versionEnd)) {
                throw new DomainException(
                    'The price-list version does not cover the requested service date.',
                );
            }
        }

        return [
            $serviceDate,
            $serviceDate,
        ];
    }

    private function assertRelationshipCoversServiceDate(
        OrganizationRelationship $relationship,
        string $serviceDate,
    ): void {
        $date = CarbonImmutable::parse(
            $serviceDate,
        )->startOfDay();

        $validFrom = $relationship->getAttribute(
            'valid_from',
        );

        if ($validFrom !== null) {
            if (! $validFrom instanceof DateTimeInterface) {
                throw new LogicException(
                    'The commercial relationship has an invalid start date.',
                );
            }

            if (
                CarbonImmutable::instance($validFrom)
                    ->startOfDay()
                    ->isAfter($date)
            ) {
                throw new DomainException(
                    'The commercial relationship does not cover the route service date.',
                );
            }
        }

        $validUntil = $relationship->getAttribute(
            'valid_until',
        );

        if ($validUntil === null) {
            return;
        }

        if (! $validUntil instanceof DateTimeInterface) {
            throw new LogicException(
                'The commercial relationship has an invalid end date.',
            );
        }

        if (
            CarbonImmutable::instance($validUntil)
                ->startOfDay()
                ->isBefore($date)
        ) {
            throw new DomainException(
                'The commercial relationship does not cover the route service date.',
            );
        }
    }

    /**
     * @return array{0:string,1:string}
     */
    private function monthlyVersionPeriod(
        CarbonImmutable $monthStart,
        PriceListVersion $priceListVersion,
    ): array {
        $validFrom = $priceListVersion->getAttribute(
            'valid_from',
        );

        if (! $validFrom instanceof DateTimeInterface) {
            throw new LogicException(
                'An applicable price-list version must have a valid start date.',
            );
        }

        $versionStart = CarbonImmutable::instance(
            $validFrom,
        )->startOfDay();

        $validUntil = $priceListVersion->getAttribute(
            'valid_until',
        );

        $versionEnd = null;

        if ($validUntil !== null) {
            if (! $validUntil instanceof DateTimeInterface) {
                throw new LogicException(
                    'The price-list version has an invalid end date.',
                );
            }

            $versionEnd = CarbonImmutable::instance(
                $validUntil,
            )->startOfDay();

            if ($versionEnd->isBefore($versionStart)) {
                throw new LogicException(
                    'The price-list version has an invalid effective period.',
                );
            }
        }

        $monthEnd = $monthStart->endOfMonth()->startOfDay();

        $scopeStart = $versionStart->isAfter($monthStart)
            ? $versionStart
            : $monthStart;

        $scopeEnd = (
            $versionEnd !== null
            && $versionEnd->isBefore($monthEnd)
        )
            ? $versionEnd
            : $monthEnd;

        if ($scopeEnd->isBefore($scopeStart)) {
            throw new DomainException(
                'The price-list version does not intersect the requested calendar month.',
            );
        }

        return [
            $scopeStart->format('Y-m-d'),
            $scopeEnd->format('Y-m-d'),
        ];
    }

    private function assertRelationshipCoversPeriod(
        OrganizationRelationship $relationship,
        string $periodStart,
        string $periodEnd,
    ): void {
        $scopeStart = CarbonImmutable::parse(
            $periodStart,
        )->startOfDay();

        $scopeEnd = CarbonImmutable::parse(
            $periodEnd,
        )->startOfDay();

        $validFrom = $relationship->getAttribute(
            'valid_from',
        );

        if ($validFrom !== null) {
            if (! $validFrom instanceof DateTimeInterface) {
                throw new LogicException(
                    'The commercial relationship has an invalid start date.',
                );
            }

            if (
                CarbonImmutable::instance($validFrom)
                    ->isAfter($scopeStart)
            ) {
                throw new DomainException(
                    'The commercial relationship does not cover the complete monthly conditional scope period.',
                );
            }
        }

        $validUntil = $relationship->getAttribute(
            'valid_until',
        );

        if ($validUntil === null) {
            return;
        }

        if (! $validUntil instanceof DateTimeInterface) {
            throw new LogicException(
                'The commercial relationship has an invalid end date.',
            );
        }

        if (
            CarbonImmutable::instance($validUntil)
                ->isBefore($scopeEnd)
        ) {
            throw new DomainException(
                'The commercial relationship does not cover the complete monthly conditional scope period.',
            );
        }
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

    private function positiveInteger(
        mixed $value,
        string $label,
    ): int {
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (
            is_string($value)
            && preg_match('/^[1-9]\d*$/D', $value) === 1
        ) {
            return (int) $value;
        }

        throw new LogicException(
            $label.' must be a positive integer.',
        );
    }
}
