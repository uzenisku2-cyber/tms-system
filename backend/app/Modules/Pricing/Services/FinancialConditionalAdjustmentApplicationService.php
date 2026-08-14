<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Organizations\OrganizationContext;
use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Models\FinancialConditionalAdjustment;
use App\Modules\Pricing\Models\FinancialConditionalAdjustmentSource;
use App\Modules\Pricing\Models\PriceListConditionalRule;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DomainException;
use Illuminate\Support\Facades\DB;
use LogicException;

final class FinancialConditionalAdjustmentApplicationService
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly FinancialConditionalScopeSourceResolver $scopeSourceResolver,
        private readonly FinancialConditionalAdjustmentPersistenceService $persistenceService,
    ) {}

    public function createInitialMonthlyDriverAdjustment(
        int $conditionalRuleId,
        int $performedByDriverId,
        string $calendarMonth,
        int $calculatedByUserId,
        DateTimeInterface $calculatedAt,
    ): FinancialConditionalAdjustment {
        $this->assertPositiveIdentifier(
            $conditionalRuleId,
            'Conditional-rule identifier',
        );

        $this->assertPositiveIdentifier(
            $performedByDriverId,
            'Performing-driver identifier',
        );

        $this->assertPositiveIdentifier(
            $calculatedByUserId,
            'Calculating-user identifier',
        );

        return DB::transaction(
            function () use (
                $conditionalRuleId,
                $performedByDriverId,
                $calendarMonth,
                $calculatedByUserId,
                $calculatedAt,
            ): FinancialConditionalAdjustment {
                $scope = $this->scopeSourceResolver
                    ->resolveMonthlyDriverSources(
                        conditionalRuleId: $conditionalRuleId,
                        performedByDriverId: $performedByDriverId,
                        calendarMonth: $calendarMonth,
                    );

                $this->assertResolvedScopeIdentity(
                    $scope,
                    conditionalRuleId: $conditionalRuleId,
                    performedByDriverId: $performedByDriverId,
                );

                $adjustment = $this->persistenceService
                    ->createInitialAdjustment(
                        conditionalRuleId: $conditionalRuleId,
                        sourceFinancialCalculationIds: $scope['financial_calculation_ids'],
                        calculatedByUserId: $calculatedByUserId,
                        calculatedAt: $calculatedAt,
                    );

                $this->assertAdjustmentMatchesResolvedScope(
                    $adjustment,
                    $scope,
                );

                return $adjustment;
            },
            3,
        );
    }

    public function recalculateMonthlyDriverAdjustment(
        int $supersedesAdjustmentId,
        int $calculatedByUserId,
        DateTimeInterface $calculatedAt,
    ): FinancialConditionalAdjustment {
        $this->assertPositiveIdentifier(
            $supersedesAdjustmentId,
            'Superseded-adjustment identifier',
        );

        $this->assertPositiveIdentifier(
            $calculatedByUserId,
            'Calculating-user identifier',
        );

        $organizationId = $this->organizationContext->requireId();

        return DB::transaction(
            function () use (
                $supersedesAdjustmentId,
                $calculatedByUserId,
                $calculatedAt,
                $organizationId,
            ): FinancialConditionalAdjustment {
                $superseded = FinancialConditionalAdjustment::query()
                    ->whereKey($supersedesAdjustmentId)
                    ->where(
                        'organization_id',
                        $organizationId,
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $superseded instanceof FinancialConditionalAdjustment) {
                    throw new DomainException(
                        'The superseded conditional adjustment is unavailable.',
                    );
                }

                if (
                    $superseded->getAttribute('evaluation_scope')
                    !== PriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_DRIVER
                ) {
                    throw new DomainException(
                        'Only a monthly-driver conditional adjustment may use this application path.',
                    );
                }

                $conditionalRuleId = $this->positiveInteger(
                    $superseded->getAttribute(
                        'price_list_conditional_rule_id',
                    ),
                    'Superseded conditional-rule identifier',
                );

                $performedByDriverId = $this->positiveInteger(
                    $superseded->getAttribute(
                        'performed_by_driver_id',
                    ),
                    'Superseded performing-driver identifier',
                );

                $supersededPeriodStart = $this->dateString(
                    $superseded->getAttribute('period_start'),
                    'Superseded period start',
                );

                $calendarMonth = substr(
                    $supersededPeriodStart,
                    0,
                    7,
                );

                $scope = $this->scopeSourceResolver
                    ->resolveMonthlyDriverSources(
                        conditionalRuleId: $conditionalRuleId,
                        performedByDriverId: $performedByDriverId,
                        calendarMonth: $calendarMonth,
                    );

                $this->assertResolvedScopeIdentity(
                    $scope,
                    conditionalRuleId: $conditionalRuleId,
                    performedByDriverId: $performedByDriverId,
                );

                $this->assertSupersededAdjustmentMatchesResolvedScope(
                    $superseded,
                    $scope,
                );

                $adjustment = $this->persistenceService
                    ->createRecalculatedAdjustment(
                        supersedesAdjustmentId: $supersedesAdjustmentId,
                        sourceFinancialCalculationIds: $scope['financial_calculation_ids'],
                        calculatedByUserId: $calculatedByUserId,
                        calculatedAt: $calculatedAt,
                    );

                $this->assertAdjustmentMatchesResolvedScope(
                    $adjustment,
                    $scope,
                );

                if (
                    $this->positiveInteger(
                        $adjustment->getAttribute(
                            'supersedes_adjustment_id',
                        ),
                        'New supersedes-adjustment identifier',
                    ) !== $supersedesAdjustmentId
                ) {
                    throw new LogicException(
                        'The recalculated conditional adjustment does not supersede the requested predecessor.',
                    );
                }

                return $adjustment;
            },
            3,
        );
    }

    public function createInitialPerRouteAdjustment(
        int $conditionalRuleId,
        int $dailyReportId,
        int $calculatedByUserId,
        DateTimeInterface $calculatedAt,
    ): FinancialConditionalAdjustment {
        $this->assertPositiveIdentifier(
            $conditionalRuleId,
            'Conditional-rule identifier',
        );

        $this->assertPositiveIdentifier(
            $dailyReportId,
            'Daily-report identifier',
        );

        $this->assertPositiveIdentifier(
            $calculatedByUserId,
            'Calculating-user identifier',
        );

        return DB::transaction(
            function () use (
                $conditionalRuleId,
                $dailyReportId,
                $calculatedByUserId,
                $calculatedAt,
            ): FinancialConditionalAdjustment {
                $scope = $this->scopeSourceResolver
                    ->resolvePerRouteSources(
                        conditionalRuleId: $conditionalRuleId,
                        dailyReportId: $dailyReportId,
                    );

                $this->assertResolvedPerRouteScopeIdentity(
                    $scope,
                    conditionalRuleId: $conditionalRuleId,
                    dailyReportId: $dailyReportId,
                );

                $adjustment = $this->persistenceService
                    ->createInitialAdjustment(
                        conditionalRuleId: $conditionalRuleId,
                        sourceFinancialCalculationIds: $scope['financial_calculation_ids'],
                        calculatedByUserId: $calculatedByUserId,
                        calculatedAt: $calculatedAt,
                    );

                $this->assertAdjustmentMatchesResolvedScope(
                    $adjustment,
                    $scope,
                );

                return $adjustment;
            },
            3,
        );
    }

    public function recalculatePerRouteAdjustment(
        int $supersedesAdjustmentId,
        int $calculatedByUserId,
        DateTimeInterface $calculatedAt,
    ): FinancialConditionalAdjustment {
        $this->assertPositiveIdentifier(
            $supersedesAdjustmentId,
            'Superseded-adjustment identifier',
        );

        $this->assertPositiveIdentifier(
            $calculatedByUserId,
            'Calculating-user identifier',
        );

        $organizationId = $this->organizationContext->requireId();

        return DB::transaction(
            function () use (
                $supersedesAdjustmentId,
                $calculatedByUserId,
                $calculatedAt,
                $organizationId,
            ): FinancialConditionalAdjustment {
                $superseded = FinancialConditionalAdjustment::query()
                    ->whereKey($supersedesAdjustmentId)
                    ->where(
                        'organization_id',
                        $organizationId,
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $superseded instanceof FinancialConditionalAdjustment) {
                    throw new DomainException(
                        'The superseded conditional adjustment is unavailable.',
                    );
                }

                if (
                    $superseded->getAttribute('evaluation_scope')
                    !== PriceListConditionalRule::EVALUATION_SCOPE_PER_ROUTE
                ) {
                    throw new DomainException(
                        'Only a per-route conditional adjustment may use this application path.',
                    );
                }

                $conditionalRuleId = $this->positiveInteger(
                    $superseded->getAttribute(
                        'price_list_conditional_rule_id',
                    ),
                    'Superseded conditional-rule identifier',
                );

                $sourceMemberships = $superseded->sources()
                    ->orderBy('source_position')
                    ->get();

                if ($sourceMemberships->count() !== 1) {
                    throw new LogicException(
                        'A superseded per-route conditional adjustment must have exactly one source.',
                    );
                }

                $sourceMembership = $sourceMemberships->first();

                if (
                    ! $sourceMembership
                    instanceof FinancialConditionalAdjustmentSource
                ) {
                    throw new LogicException(
                        'The superseded per-route source membership could not be resolved.',
                    );
                }

                $sourceCalculationId = $this->positiveInteger(
                    $sourceMembership->getAttribute(
                        'financial_calculation_id',
                    ),
                    'Superseded source financial-calculation identifier',
                );

                $sourceCalculation = FinancialCalculation::query()
                    ->whereKey($sourceCalculationId)
                    ->first([
                        'id',
                        'daily_report_id',
                    ]);

                if (! $sourceCalculation instanceof FinancialCalculation) {
                    throw new DomainException(
                        'The superseded per-route source calculation is unavailable.',
                    );
                }

                $dailyReportId = $this->positiveInteger(
                    $sourceCalculation->getAttribute(
                        'daily_report_id',
                    ),
                    'Superseded source daily-report identifier',
                );

                $scope = $this->scopeSourceResolver
                    ->resolvePerRouteSources(
                        conditionalRuleId: $conditionalRuleId,
                        dailyReportId: $dailyReportId,
                    );

                $this->assertResolvedPerRouteScopeIdentity(
                    $scope,
                    conditionalRuleId: $conditionalRuleId,
                    dailyReportId: $dailyReportId,
                );

                $this->assertSupersededAdjustmentMatchesResolvedScope(
                    $superseded,
                    $scope,
                );

                $adjustment = $this->persistenceService
                    ->createRecalculatedAdjustment(
                        supersedesAdjustmentId: $supersedesAdjustmentId,
                        sourceFinancialCalculationIds: $scope['financial_calculation_ids'],
                        calculatedByUserId: $calculatedByUserId,
                        calculatedAt: $calculatedAt,
                    );

                $this->assertAdjustmentMatchesResolvedScope(
                    $adjustment,
                    $scope,
                );

                if (
                    $this->positiveInteger(
                        $adjustment->getAttribute(
                            'supersedes_adjustment_id',
                        ),
                        'New supersedes-adjustment identifier',
                    ) !== $supersedesAdjustmentId
                ) {
                    throw new LogicException(
                        'The recalculated conditional adjustment does not supersede the requested predecessor.',
                    );
                }

                return $adjustment;
            },
            3,
        );
    }

    /**
     * @param array{
     *     organization_id:int,
     *     organization_relationship_id:int,
     *     price_list_id:int,
     *     price_list_version_id:int,
     *     performed_by_driver_id:int,
     *     period_start:string,
     *     period_end:string,
     *     daily_report_ids:list<int>,
     *     financial_calculation_ids:list<int>
     * } $scope
     */
    private function assertResolvedScopeIdentity(
        array $scope,
        int $conditionalRuleId,
        int $performedByDriverId,
    ): void {
        if (
            ($scope['performed_by_driver_id'] ?? null)
            !== $performedByDriverId
        ) {
            throw new LogicException(
                'The resolved conditional scope changed the performing driver.',
            );
        }

        if (
            ! isset($scope['financial_calculation_ids'])
            || ! is_array($scope['financial_calculation_ids'])
            || $scope['financial_calculation_ids'] === []
        ) {
            throw new LogicException(
                'The resolved conditional scope contains no financial sources.',
            );
        }

        $rule = PriceListConditionalRule::query()
            ->whereKey($conditionalRuleId)
            ->first();

        if (! $rule instanceof PriceListConditionalRule) {
            throw new DomainException(
                'The conditional pricing rule became unavailable during scope application.',
            );
        }

        if (
            $this->positiveInteger(
                $rule->getAttribute('price_list_version_id'),
                'Conditional-rule price-list version',
            )
            !== ($scope['price_list_version_id'] ?? null)
        ) {
            throw new LogicException(
                'The resolved conditional scope does not match the rule price-list version.',
            );
        }
    }

    /**
     * @param array{
     *     organization_id:int,
     *     organization_relationship_id:int,
     *     price_list_id:int,
     *     price_list_version_id:int,
     *     performed_by_driver_id:int,
     *     period_start:string,
     *     period_end:string,
     *     daily_report_ids:list<int>,
     *     financial_calculation_ids:list<int>
     * } $scope
     */
    private function assertResolvedPerRouteScopeIdentity(
        array $scope,
        int $conditionalRuleId,
        int $dailyReportId,
    ): void {
        if (
            ($scope['daily_report_ids'] ?? null)
            !== [$dailyReportId]
        ) {
            throw new LogicException(
                'The resolved per-route conditional scope changed the daily report.',
            );
        }

        if (
            ! isset($scope['financial_calculation_ids'])
            || ! is_array($scope['financial_calculation_ids'])
            || count($scope['financial_calculation_ids']) !== 1
        ) {
            throw new LogicException(
                'The resolved per-route conditional scope must contain exactly one financial source.',
            );
        }

        if (
            ($scope['period_start'] ?? null)
            !== ($scope['period_end'] ?? null)
        ) {
            throw new LogicException(
                'The resolved per-route conditional scope must cover exactly one service date.',
            );
        }

        $performedByDriverId = $this->positiveInteger(
            $scope['performed_by_driver_id'] ?? null,
            'Resolved per-route performing-driver identifier',
        );

        $this->assertResolvedScopeIdentity(
            $scope,
            conditionalRuleId: $conditionalRuleId,
            performedByDriverId: $performedByDriverId,
        );
    }

    /**
     * @param array{
     *     organization_id:int,
     *     organization_relationship_id:int,
     *     price_list_id:int,
     *     price_list_version_id:int,
     *     performed_by_driver_id:int,
     *     period_start:string,
     *     period_end:string,
     *     daily_report_ids:list<int>,
     *     financial_calculation_ids:list<int>
     * } $scope
     */
    private function assertSupersededAdjustmentMatchesResolvedScope(
        FinancialConditionalAdjustment $superseded,
        array $scope,
    ): void {
        $this->assertAdjustmentMatchesResolvedScope(
            $superseded,
            $scope,
        );
    }

    /**
     * @param array{
     *     organization_id:int,
     *     organization_relationship_id:int,
     *     price_list_id:int,
     *     price_list_version_id:int,
     *     performed_by_driver_id:int,
     *     period_start:string,
     *     period_end:string,
     *     daily_report_ids:list<int>,
     *     financial_calculation_ids:list<int>
     * } $scope
     */
    private function assertAdjustmentMatchesResolvedScope(
        FinancialConditionalAdjustment $adjustment,
        array $scope,
    ): void {
        $checks = [
            'organization_id' => 'organization_id',
            'organization_relationship_id' => 'organization_relationship_id',
            'price_list_id' => 'price_list_id',
            'price_list_version_id' => 'price_list_version_id',
            'performed_by_driver_id' => 'performed_by_driver_id',
        ];

        foreach ($checks as $attribute => $scopeKey) {
            if (
                $this->positiveInteger(
                    $adjustment->getAttribute($attribute),
                    'Conditional adjustment '.$attribute,
                ) !== ($scope[$scopeKey] ?? null)
            ) {
                throw new LogicException(
                    'The persisted conditional adjustment does not match the resolved scope identity.',
                );
            }
        }

        $periodStart = $this->dateString(
            $adjustment->getAttribute('period_start'),
            'Conditional adjustment period start',
        );

        $periodEnd = $this->dateString(
            $adjustment->getAttribute('period_end'),
            'Conditional adjustment period end',
        );

        if (
            $periodStart !== ($scope['period_start'] ?? null)
            || $periodEnd !== ($scope['period_end'] ?? null)
        ) {
            throw new DomainException(
                'The resolved conditional scope period changed before persistence completed.',
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
        if (! is_int($value) || $value < 1) {
            throw new LogicException(
                $label.' must be a positive integer.',
            );
        }

        return $value;
    }

    private function dateString(
        mixed $value,
        string $label,
    ): string {
        if (! $value instanceof DateTimeInterface) {
            throw new LogicException(
                $label.' must be a date.',
            );
        }

        return CarbonImmutable::instance(
            $value,
        )->format('Y-m-d');
    }
}
