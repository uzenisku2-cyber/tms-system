<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Organizations\OrganizationContext;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Models\FinancialConditionalAdjustment;
use App\Modules\Pricing\Models\PriceListConditionalBand;
use App\Modules\Pricing\Models\PriceListConditionalRule;
use App\Modules\Pricing\Models\PriceListVersion;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DomainException;
use Illuminate\Support\Facades\DB;
use LogicException;

final class FinancialConditionalAdjustmentPersistenceService
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly ConditionalPricingEngine $conditionalPricingEngine,
    ) {}

    /**
     * @param  list<int>  $sourceFinancialCalculationIds
     */
    public function createInitialAdjustment(
        int $conditionalRuleId,
        array $sourceFinancialCalculationIds,
        int $calculatedByUserId,
        DateTimeInterface $calculatedAt,
    ): FinancialConditionalAdjustment {
        return $this->persistAdjustment(
            conditionalRuleId: $conditionalRuleId,
            sourceFinancialCalculationIds: $sourceFinancialCalculationIds,
            calculatedByUserId: $calculatedByUserId,
            calculatedAt: $calculatedAt,
            supersedesAdjustmentId: null,
        );
    }

    /**
     * @param  list<int>  $sourceFinancialCalculationIds
     */
    public function createRecalculatedAdjustment(
        int $supersedesAdjustmentId,
        array $sourceFinancialCalculationIds,
        int $calculatedByUserId,
        DateTimeInterface $calculatedAt,
    ): FinancialConditionalAdjustment {
        if ($supersedesAdjustmentId < 1) {
            throw new DomainException(
                'A positive superseded-adjustment identifier is required.',
            );
        }

        return $this->persistAdjustment(
            conditionalRuleId: null,
            sourceFinancialCalculationIds: $sourceFinancialCalculationIds,
            calculatedByUserId: $calculatedByUserId,
            calculatedAt: $calculatedAt,
            supersedesAdjustmentId: $supersedesAdjustmentId,
        );
    }

    /**
     * @param  list<int>  $sourceFinancialCalculationIds
     */
    private function persistAdjustment(
        ?int $conditionalRuleId,
        array $sourceFinancialCalculationIds,
        int $calculatedByUserId,
        DateTimeInterface $calculatedAt,
        ?int $supersedesAdjustmentId,
    ): FinancialConditionalAdjustment {
        if ($conditionalRuleId !== null && $conditionalRuleId < 1) {
            throw new DomainException(
                'A positive conditional-rule identifier is required.',
            );
        }

        if ($conditionalRuleId === null && $supersedesAdjustmentId === null) {
            throw new LogicException(
                'A conditional rule or superseded adjustment is required.',
            );
        }

        if ($calculatedByUserId < 1) {
            throw new DomainException(
                'A positive calculating-user identifier is required.',
            );
        }

        $sourceIds = $this->normalizedSourceIds(
            $sourceFinancialCalculationIds,
        );
        $organizationId = $this->organizationContext->requireId();
        $calculatedMoment = CarbonImmutable::instance($calculatedAt);

        return DB::transaction(
            function () use (
                $conditionalRuleId,
                $sourceIds,
                $calculatedByUserId,
                $calculatedMoment,
                $organizationId,
                $supersedesAdjustmentId,
            ): FinancialConditionalAdjustment {
                $supersedesAdjustment = null;

                if ($supersedesAdjustmentId !== null) {
                    $supersedesAdjustment =
                        FinancialConditionalAdjustment::query()
                            ->whereKey($supersedesAdjustmentId)
                            ->where(
                                'organization_id',
                                $organizationId,
                            )
                            ->lockForUpdate()
                            ->first();

                    if (
                        ! $supersedesAdjustment instanceof FinancialConditionalAdjustment
                    ) {
                        throw new DomainException(
                            'The superseded conditional adjustment is unavailable.',
                        );
                    }

                    $conditionalRuleId = $this->positiveInteger(
                        $supersedesAdjustment->getAttribute(
                            'price_list_conditional_rule_id',
                        ),
                        'Superseded conditional-rule identifier',
                    );
                }

                if ($conditionalRuleId === null) {
                    throw new LogicException(
                        'The conditional rule could not be resolved.',
                    );
                }

                $rule = PriceListConditionalRule::query()
                    ->with('bands')
                    ->whereKey($conditionalRuleId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $priceListVersionId = $this->positiveInteger(
                    $rule->getAttribute('price_list_version_id'),
                    'Conditional-rule price-list version',
                );

                $priceListVersion = PriceListVersion::query()
                    ->whereKey($priceListVersionId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    ! $priceListVersion->isActive()
                    && ! $priceListVersion->isReplaced()
                    && ! $priceListVersion->isExpired()
                ) {
                    throw new DomainException(
                        'Conditional financial adjustment requires an active, replaced or expired price-list version.',
                    );
                }

                [
                    $priceListVersionStart,
                    $priceListVersionEnd,
                ] = $this->priceListVersionBounds(
                    $priceListVersion,
                );

                $priceListId = $this->positiveInteger(
                    $priceListVersion->getAttribute('price_list_id'),
                    'Price-list identifier',
                );

                $sourceCollection = FinancialCalculation::query()
                    ->with('lines')
                    ->whereIn('id', $sourceIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy(
                        static fn (FinancialCalculation $calculation): int => (int) $calculation->getKey(),
                    );

                if ($sourceCollection->count() !== count($sourceIds)) {
                    throw new DomainException(
                        'One or more source financial calculations are unavailable.',
                    );
                }

                $sources = [];
                foreach ($sourceIds as $sourceId) {
                    $source = $sourceCollection->get($sourceId);
                    if (! $source instanceof FinancialCalculation) {
                        throw new LogicException(
                            'Source calculation ordering could not be reconstructed.',
                        );
                    }
                    $sources[] = $source;
                }

                $first = $sources[0];
                $relationshipId = $this->positiveInteger(
                    $first->getAttribute('organization_relationship_id'),
                    'Organization relationship identifier',
                );
                $currency = $this->requiredString(
                    $first->getAttribute('currency'),
                    'Financial calculation currency',
                );

                if (
                    $this->positiveInteger(
                        $first->getAttribute('organization_id'),
                        'Financial organization identifier',
                    ) !== $organizationId
                ) {
                    throw new DomainException(
                        'The source financial calculation is outside the active organization context.',
                    );
                }

                if (
                    $this->positiveInteger(
                        $first->getAttribute('price_list_id'),
                        'Financial price-list identifier',
                    ) !== $priceListId
                ) {
                    throw new DomainException(
                        'The source financial calculation belongs to another price list.',
                    );
                }

                if (
                    $this->positiveInteger(
                        $first->getAttribute('price_list_version_id'),
                        'Financial price-list version identifier',
                    ) !== $priceListVersionId
                ) {
                    throw new DomainException(
                        'The source financial calculation does not use the conditional-rule version.',
                    );
                }

                $snapshots = [];

                foreach ($sources as $source) {
                    if (! $source->isApproved() && ! $source->isClosed()) {
                        throw new DomainException(
                            'Conditional financial adjustments require approved or closed source calculations.',
                        );
                    }

                    if (
                        $this->positiveInteger(
                            $source->getAttribute('organization_id'),
                            'Financial organization identifier',
                        ) !== $organizationId
                    ) {
                        throw new DomainException(
                            'Conditional scope cannot mix financial organizations.',
                        );
                    }

                    if (
                        $this->positiveInteger(
                            $source->getAttribute('organization_relationship_id'),
                            'Organization relationship identifier',
                        ) !== $relationshipId
                    ) {
                        throw new DomainException(
                            'Conditional scope cannot mix organization relationships.',
                        );
                    }

                    if (
                        $this->positiveInteger(
                            $source->getAttribute('price_list_id'),
                            'Financial price-list identifier',
                        ) !== $priceListId
                    ) {
                        throw new DomainException(
                            'Conditional scope cannot mix price lists.',
                        );
                    }

                    if (
                        $this->positiveInteger(
                            $source->getAttribute('price_list_version_id'),
                            'Financial price-list version identifier',
                        ) !== $priceListVersionId
                    ) {
                        throw new DomainException(
                            'Conditional scope cannot mix price-list versions.',
                        );
                    }

                    if (
                        $this->requiredString(
                            $source->getAttribute('currency'),
                            'Financial calculation currency',
                        ) !== $currency
                    ) {
                        throw new DomainException(
                            'Conditional scope cannot mix currencies.',
                        );
                    }

                    $snapshot = $source->getAttribute('input_snapshot');
                    if (! is_array($snapshot)) {
                        throw new DomainException(
                            'A source financial calculation has no valid immutable input snapshot.',
                        );
                    }

                    $snapshotStatus = $snapshot['status'] ?? null;
                    if (
                        ! is_string($snapshotStatus)
                        || ! in_array(
                            $snapshotStatus,
                            [
                                DailyReport::STATUS_APPROVED,
                                DailyReport::STATUS_CLOSED,
                            ],
                            true,
                        )
                    ) {
                        throw new DomainException(
                            'Conditional adjustment sources must originate from approved or closed daily-report snapshots.',
                        );
                    }

                    $this->positiveInteger(
                        $snapshot['performed_by_driver_id'] ?? null,
                        'Snapshot performing-driver identifier',
                    );

                    $serviceDate = $this->requiredString(
                        $snapshot['service_date'] ?? null,
                        'Snapshot service date',
                    );

                    $parsedServiceDate = CarbonImmutable::parse(
                        $serviceDate,
                    )->format('Y-m-d');

                    if ($parsedServiceDate !== $serviceDate) {
                        throw new DomainException(
                            'A source snapshot contains an invalid service date.',
                        );
                    }

                    $serviceMoment = CarbonImmutable::parse(
                        $serviceDate,
                    )->startOfDay();

                    if (
                        $serviceMoment->isBefore($priceListVersionStart)
                        || (
                            $priceListVersionEnd !== null
                            && $serviceMoment->isAfter(
                                $priceListVersionEnd,
                            )
                        )
                    ) {
                        throw new DomainException(
                            'A conditional-adjustment source falls outside the price-list version period.',
                        );
                    }

                    $snapshots[] = $snapshot;
                }

                $baseItemAmounts = $this->baseItemAmounts($rule, $sources);

                $engineResult = $this->conditionalPricingEngine->evaluateRule(
                    $rule,
                    $snapshots,
                    $baseItemAmounts,
                );

                $aggregate = $engineResult['aggregate'] ?? null;
                $evaluation = $engineResult['evaluation'] ?? null;

                if (! is_array($aggregate) || ! is_array($evaluation)) {
                    throw new LogicException(
                        'The conditional pricing engine returned an invalid result.',
                    );
                }

                $evaluationScope = $this->requiredString(
                    $rule->getAttribute('evaluation_scope'),
                    'Conditional evaluation scope',
                );

                if (($aggregate['evaluation_scope'] ?? null) !== $evaluationScope) {
                    throw new LogicException(
                        'Conditional aggregate scope does not match its rule.',
                    );
                }

                $performedByDriverId = $this->positiveInteger(
                    $aggregate['driver_id'] ?? null,
                    'Conditional aggregate driver identifier',
                );

                [$periodStart, $periodEnd] = $this->periodRange(
                    $evaluationScope,
                    $aggregate['period'] ?? null,
                    $priceListVersionStart,
                    $priceListVersionEnd,
                );

                $matchedBand = $this->matchedBand($rule, $evaluation);

                $adjustmentValue = $this->nullableNonNegativeDecimal(
                    $evaluation['adjustment_value'] ?? null,
                    'Conditional adjustment value',
                );

                $conditionalAmount = $this->nonNegativeDecimal(
                    $evaluation['conditional_amount'] ?? null,
                    'Conditional amount',
                );

                if ($matchedBand === null) {
                    if (
                        $adjustmentValue !== null
                        || bccomp($conditionalAmount, '0', 2) !== 0
                    ) {
                        throw new LogicException(
                            'An unmatched conditional rule must produce a zero adjustment.',
                        );
                    }
                } else {
                    $bandAdjustment = $this->nonNegativeDecimal(
                        $matchedBand->getAttribute('adjustment_value'),
                        'Matched-band adjustment value',
                    );

                    if (
                        $adjustmentValue === null
                        || bccomp($adjustmentValue, $bandAdjustment, 4) !== 0
                    ) {
                        throw new LogicException(
                            'The engine adjustment does not match the selected band.',
                        );
                    }
                }

                $calculationVersion = 1;

                if ($supersedesAdjustmentId === null) {
                    $existing = FinancialConditionalAdjustment::query()
                        ->where(
                            'price_list_conditional_rule_id',
                            $conditionalRuleId,
                        )
                        ->where(
                            'performed_by_driver_id',
                            $performedByDriverId,
                        )
                        ->whereDate('period_start', $periodStart)
                        ->whereDate('period_end', $periodEnd)
                        ->where('calculation_version', 1)
                        ->lockForUpdate()
                        ->exists();

                    if ($existing) {
                        throw new DomainException(
                            'The initial conditional adjustment for this scope already exists.',
                        );
                    }
                } else {
                    if (
                        ! $supersedesAdjustment instanceof FinancialConditionalAdjustment
                    ) {
                        throw new LogicException(
                            'The superseded conditional adjustment was not locked.',
                        );
                    }

                    if (
                        $this->positiveInteger(
                            $supersedesAdjustment->getAttribute(
                                'organization_id',
                            ),
                            'Superseded financial organization identifier',
                        ) !== $organizationId
                    ) {
                        throw new DomainException(
                            'A recalculation cannot cross financial organizations.',
                        );
                    }

                    if (
                        $this->positiveInteger(
                            $supersedesAdjustment->getAttribute(
                                'organization_relationship_id',
                            ),
                            'Superseded relationship identifier',
                        ) !== $relationshipId
                    ) {
                        throw new DomainException(
                            'A recalculation cannot cross organization relationships.',
                        );
                    }

                    if (
                        $this->positiveInteger(
                            $supersedesAdjustment->getAttribute(
                                'price_list_id',
                            ),
                            'Superseded price-list identifier',
                        ) !== $priceListId
                    ) {
                        throw new DomainException(
                            'A recalculation cannot cross price lists.',
                        );
                    }

                    if (
                        $this->positiveInteger(
                            $supersedesAdjustment->getAttribute(
                                'price_list_version_id',
                            ),
                            'Superseded price-list version identifier',
                        ) !== $priceListVersionId
                    ) {
                        throw new DomainException(
                            'A recalculation cannot cross price-list versions.',
                        );
                    }

                    if (
                        $this->positiveInteger(
                            $supersedesAdjustment->getAttribute(
                                'price_list_conditional_rule_id',
                            ),
                            'Superseded conditional-rule identifier',
                        ) !== $conditionalRuleId
                    ) {
                        throw new DomainException(
                            'A recalculation cannot cross conditional rules.',
                        );
                    }

                    if (
                        $this->positiveInteger(
                            $supersedesAdjustment->getAttribute(
                                'performed_by_driver_id',
                            ),
                            'Superseded driver identifier',
                        ) !== $performedByDriverId
                    ) {
                        throw new DomainException(
                            'A recalculation cannot cross drivers.',
                        );
                    }

                    if (
                        $this->requiredString(
                            $supersedesAdjustment->getAttribute(
                                'evaluation_scope',
                            ),
                            'Superseded evaluation scope',
                        ) !== $evaluationScope
                    ) {
                        throw new DomainException(
                            'A recalculation cannot change evaluation scope.',
                        );
                    }

                    if (
                        $this->requiredString(
                            $supersedesAdjustment->getAttribute(
                                'currency',
                            ),
                            'Superseded currency',
                        ) !== $currency
                    ) {
                        throw new DomainException(
                            'A recalculation cannot change currency.',
                        );
                    }

                    $supersededPeriodStart =
                        $supersedesAdjustment->getAttribute(
                            'period_start',
                        );
                    $supersededPeriodEnd =
                        $supersedesAdjustment->getAttribute(
                            'period_end',
                        );

                    if (
                        ! $supersededPeriodStart instanceof DateTimeInterface
                        || ! $supersededPeriodEnd instanceof DateTimeInterface
                    ) {
                        throw new LogicException(
                            'The superseded adjustment has an invalid period.',
                        );
                    }

                    if (
                        CarbonImmutable::instance(
                            $supersededPeriodStart,
                        )->format('Y-m-d') !== $periodStart
                        || CarbonImmutable::instance(
                            $supersededPeriodEnd,
                        )->format('Y-m-d') !== $periodEnd
                    ) {
                        throw new DomainException(
                            'A recalculation must preserve its evaluation period.',
                        );
                    }

                    $existingChild =
                        FinancialConditionalAdjustment::query()
                            ->where(
                                'supersedes_adjustment_id',
                                $supersedesAdjustmentId,
                            )
                            ->lockForUpdate()
                            ->first();

                    if (
                        $existingChild instanceof FinancialConditionalAdjustment
                    ) {
                        throw new DomainException(
                            'The selected conditional adjustment has already been superseded.',
                        );
                    }

                    $latest =
                        FinancialConditionalAdjustment::query()
                            ->where(
                                'price_list_conditional_rule_id',
                                $conditionalRuleId,
                            )
                            ->where(
                                'performed_by_driver_id',
                                $performedByDriverId,
                            )
                            ->whereDate('period_start', $periodStart)
                            ->whereDate('period_end', $periodEnd)
                            ->orderByDesc('calculation_version')
                            ->lockForUpdate()
                            ->first();

                    if (
                        ! $latest instanceof FinancialConditionalAdjustment
                        || (int) $latest->getKey()
                            !== (int) $supersedesAdjustment->getKey()
                    ) {
                        throw new DomainException(
                            'Only the latest conditional adjustment version may be recalculated.',
                        );
                    }

                    $supersededVersion = $this->positiveInteger(
                        $supersedesAdjustment->getAttribute(
                            'calculation_version',
                        ),
                        'Superseded calculation version',
                    );

                    $calculationVersion = $supersededVersion + 1;

                    $versionCollision =
                        FinancialConditionalAdjustment::query()
                            ->where(
                                'price_list_conditional_rule_id',
                                $conditionalRuleId,
                            )
                            ->where(
                                'performed_by_driver_id',
                                $performedByDriverId,
                            )
                            ->whereDate('period_start', $periodStart)
                            ->whereDate('period_end', $periodEnd)
                            ->where(
                                'calculation_version',
                                $calculationVersion,
                            )
                            ->lockForUpdate()
                            ->exists();

                    if ($versionCollision) {
                        throw new DomainException(
                            'The next conditional adjustment version already exists.',
                        );
                    }
                }

                $evaluationSnapshot = [
                    'conditional_rule' => [
                        'id' => $conditionalRuleId,
                        'code' => $rule->getAttribute('code'),
                        'name' => $rule->getAttribute('name'),
                        'metric_type' => $rule->getAttribute('metric_type'),
                        'metric_numerator_source' => $rule->getAttribute('metric_numerator_source'),
                        'metric_denominator_source' => $rule->getAttribute('metric_denominator_source'),
                        'evaluation_scope' => $evaluationScope,
                        'reward_method' => $rule->getAttribute('reward_method'),
                        'reward_quantity_source' => $rule->getAttribute('reward_quantity_source'),
                        'reward_target_item_code' => $rule->getAttribute('reward_target_item_code'),
                        'rounding_scale' => $rule->getAttribute('rounding_scale'),
                        'rounding_method' => $rule->getAttribute('rounding_method'),
                    ],
                    'aggregate' => $aggregate,
                    'evaluation' => $evaluation,
                    'evaluation_period' => [
                        'start' => $periodStart,
                        'end' => $periodEnd,
                    ],
                    'source_financial_calculation_ids' => $sourceIds,
                    'source_calculations' => array_map(
                        static fn (FinancialCalculation $source): array => [
                            'id' => (int) $source->getKey(),
                            'public_id' => (string) $source->getAttribute('public_id'),
                            'daily_report_id' => (int) $source->getAttribute('daily_report_id'),
                            'daily_report_version' => (int) $source->getAttribute('daily_report_version'),
                            'calculation_version' => (int) $source->getAttribute('calculation_version'),
                        ],
                        $sources,
                    ),
                    'adjustment_lineage' => [
                        'calculation_version' => $calculationVersion,
                        'supersedes_adjustment_id' => $supersedesAdjustmentId,
                        'supersedes_adjustment_public_id' => $supersedesAdjustment instanceof FinancialConditionalAdjustment
                                ? (string) $supersedesAdjustment
                                    ->getAttribute('public_id')
                                : null,
                    ],
                ];

                $adjustment = FinancialConditionalAdjustment::query()->create([
                    'organization_id' => $organizationId,
                    'organization_relationship_id' => $relationshipId,
                    'price_list_id' => $priceListId,
                    'price_list_version_id' => $priceListVersionId,
                    'price_list_conditional_rule_id' => $conditionalRuleId,
                    'price_list_conditional_band_id' => $matchedBand?->getKey(),
                    'performed_by_driver_id' => $performedByDriverId,
                    'evaluation_scope' => $evaluationScope,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'calculation_version' => $calculationVersion,
                    'currency' => $currency,
                    'metric_type' => $this->requiredString(
                        $rule->getAttribute('metric_type'),
                        'Conditional metric type',
                    ),
                    'metric_numerator_source' => $this->requiredString(
                        $rule->getAttribute('metric_numerator_source'),
                        'Conditional numerator source',
                    ),
                    'metric_numerator_value' => $this->nonNegativeDecimal(
                        $aggregate['metric_numerator_value'] ?? null,
                        'Conditional numerator value',
                    ),
                    'metric_denominator_source' => $this->nullableString(
                        $rule->getAttribute('metric_denominator_source'),
                        'Conditional denominator source',
                    ),
                    'metric_denominator_value' => $this->nullableNonNegativeDecimal(
                        $aggregate['metric_denominator_value'] ?? null,
                        'Conditional denominator value',
                    ),
                    'metric_value' => $this->nonNegativeDecimal(
                        $evaluation['metric_value'] ?? null,
                        'Conditional metric value',
                    ),
                    'reward_method' => $this->requiredString(
                        $rule->getAttribute('reward_method'),
                        'Conditional reward method',
                    ),
                    'reward_quantity_source' => $this->nullableString(
                        $rule->getAttribute('reward_quantity_source'),
                        'Conditional reward quantity source',
                    ),
                    'reward_quantity_value' => $this->nullableNonNegativeDecimal(
                        $evaluation['reward_quantity_value'] ?? null,
                        'Conditional reward quantity value',
                    ),
                    'reward_target_item_code' => $this->nullableString(
                        $rule->getAttribute('reward_target_item_code'),
                        'Conditional reward target item code',
                    ),
                    'reward_target_item_amount' => $this->nullableNonNegativeDecimal(
                        $evaluation['reward_target_item_amount'] ?? null,
                        'Conditional reward target item amount',
                    ),
                    'adjustment_value' => $adjustmentValue,
                    'conditional_amount' => $conditionalAmount,
                    'evaluation_snapshot' => $evaluationSnapshot,
                    'calculated_by_user_id' => $calculatedByUserId,
                    'calculated_at' => $calculatedMoment,
                    'supersedes_adjustment_id' => $supersedesAdjustmentId,
                    'created_at' => $calculatedMoment,
                ]);

                foreach ($sources as $index => $source) {
                    $adjustment->sources()->create([
                        'financial_calculation_id' => $source->getKey(),
                        'source_position' => $index + 1,
                        'created_at' => $calculatedMoment,
                    ]);
                }

                return $adjustment->load([
                    'sources',
                    'conditionalRule',
                    'conditionalBand',
                ]);
            },
            3,
        );
    }

    /**
     * @param  array<mixed>  $sourceIds
     * @return list<int>
     */
    private function normalizedSourceIds(array $sourceIds): array
    {
        if ($sourceIds === []) {
            throw new DomainException(
                'At least one source financial calculation is required.',
            );
        }

        $normalized = [];
        $seen = [];

        foreach ($sourceIds as $value) {
            if (! is_int($value) || $value < 1) {
                throw new DomainException(
                    'Source financial calculation identifiers must be positive integers.',
                );
            }

            if (isset($seen[$value])) {
                throw new DomainException(
                    'Source financial calculation identifiers must be unique.',
                );
            }

            $seen[$value] = true;
            $normalized[] = $value;
        }

        return $normalized;
    }

    /**
     * @param  list<FinancialCalculation>  $sources
     * @return array<string, string>
     */
    private function baseItemAmounts(
        PriceListConditionalRule $rule,
        array $sources,
    ): array {
        if (
            $rule->getAttribute('reward_method')
            !== PriceListConditionalRule::REWARD_METHOD_PERCENTAGE_OF_ITEM
        ) {
            return [];
        }

        $targetCode = $this->requiredString(
            $rule->getAttribute('reward_target_item_code'),
            'Percentage reward target item code',
        );

        $total = '0.00';

        foreach ($sources as $source) {
            $matching = $source->lines
                ->where('pricing_code', $targetCode)
                ->values();

            if ($matching->count() !== 1) {
                throw new DomainException(
                    'Each source calculation must contain exactly one base line for the percentage reward target.',
                );
            }

            $line = $matching->first();
            if ($line === null) {
                throw new LogicException(
                    'Percentage reward base line is unavailable.',
                );
            }

            $amount = $this->nonNegativeDecimal(
                $line->getAttribute('line_amount'),
                'Percentage reward target base amount',
            );

            $total = bcadd($total, $amount, 2);
        }

        return [$targetCode => $total];
    }

    /**
     * @param  array<string, mixed>  $evaluation
     */
    private function matchedBand(
        PriceListConditionalRule $rule,
        array $evaluation,
    ): ?PriceListConditionalBand {
        $position = $evaluation['matched_band_position'] ?? null;

        if ($position === null) {
            return null;
        }

        $position = $this->positiveInteger(
            $position,
            'Matched conditional-band position',
        );

        $band = $rule->bands->first(
            static fn (PriceListConditionalBand $candidate): bool => (int) $candidate->getAttribute('position') === $position,
        );

        if (! $band instanceof PriceListConditionalBand) {
            throw new LogicException(
                'The engine selected a band outside the conditional rule.',
            );
        }

        return $band;
    }

    /**
     * @return array{0:CarbonImmutable,1:CarbonImmutable|null}
     */
    private function priceListVersionBounds(
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

        $start = CarbonImmutable::instance(
            $validFrom,
        )->startOfDay();

        $validUntil = $priceListVersion->getAttribute(
            'valid_until',
        );

        if ($validUntil === null) {
            return [$start, null];
        }

        if (! $validUntil instanceof DateTimeInterface) {
            throw new LogicException(
                'The price-list version has an invalid end date.',
            );
        }

        $end = CarbonImmutable::instance(
            $validUntil,
        )->startOfDay();

        if ($end->isBefore($start)) {
            throw new LogicException(
                'The price-list version has an invalid effective period.',
            );
        }

        return [$start, $end];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function periodRange(
        string $scope,
        mixed $period,
        CarbonImmutable $priceListVersionStart,
        ?CarbonImmutable $priceListVersionEnd,
    ): array {
        if (! is_string($period) || $period === '') {
            throw new LogicException(
                'The conditional aggregate has no valid period.',
            );
        }

        if (
            $scope === PriceListConditionalRule::EVALUATION_SCOPE_PER_ROUTE
        ) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $period) !== 1) {
                throw new LogicException(
                    'A per-route aggregate requires a service-date period.',
                );
            }

            $date = CarbonImmutable::parse(
                $period,
            )->startOfDay();

            if ($date->format('Y-m-d') !== $period) {
                throw new LogicException(
                    'A per-route aggregate period is invalid.',
                );
            }

            if (
                $date->isBefore($priceListVersionStart)
                || (
                    $priceListVersionEnd !== null
                    && $date->isAfter($priceListVersionEnd)
                )
            ) {
                throw new DomainException(
                    'A per-route conditional period falls outside the price-list version period.',
                );
            }

            return [$period, $period];
        }

        if (
            $scope === PriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_DRIVER
        ) {
            if (preg_match('/^\d{4}-\d{2}$/', $period) !== 1) {
                throw new LogicException(
                    'A monthly-driver aggregate requires a calendar-month period.',
                );
            }

            $monthStart = CarbonImmutable::parse(
                $period.'-01',
            )->startOfMonth();

            if ($monthStart->format('Y-m') !== $period) {
                throw new LogicException(
                    'A monthly-driver aggregate period is invalid.',
                );
            }

            $monthEnd = $monthStart->endOfMonth()->startOfDay();

            $scopeStart = $priceListVersionStart->isAfter(
                $monthStart,
            )
                ? $priceListVersionStart
                : $monthStart;

            $scopeEnd = (
                $priceListVersionEnd !== null
                && $priceListVersionEnd->isBefore($monthEnd)
            )
                ? $priceListVersionEnd
                : $monthEnd;

            if ($scopeEnd->isBefore($scopeStart)) {
                throw new DomainException(
                    'The price-list version does not intersect the monthly-driver aggregate period.',
                );
            }

            return [
                $scopeStart->format('Y-m-d'),
                $scopeEnd->format('Y-m-d'),
            ];
        }

        throw new LogicException(
            'Unsupported conditional evaluation scope.',
        );
    }

    private function positiveInteger(mixed $value, string $label): int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (
            is_string($value)
            && ctype_digit($value)
            && (int) $value > 0
        ) {
            return (int) $value;
        }

        throw new DomainException(
            $label.' must be a positive integer.',
        );
    }

    private function requiredString(mixed $value, string $label): string
    {
        if (! is_string($value) || $value === '') {
            throw new DomainException(
                $label.' must be a non-empty string.',
            );
        }

        return $value;
    }

    private function nullableString(
        mixed $value,
        string $label,
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new DomainException(
                $label.' must be a string or null.',
            );
        }

        return $value;
    }

    private function nonNegativeDecimal(
        mixed $value,
        string $label,
    ): string {
        if (
            ! is_int($value)
            && ! is_float($value)
            && ! is_string($value)
        ) {
            throw new DomainException(
                $label.' must be numeric.',
            );
        }

        $decimal = (string) $value;
        if (! is_numeric($decimal) || bccomp($decimal, '0', 6) < 0) {
            throw new DomainException(
                $label.' must be non-negative.',
            );
        }

        return $decimal;
    }

    private function nullableNonNegativeDecimal(
        mixed $value,
        string $label,
    ): ?string {
        return $value === null
            ? null
            : $this->nonNegativeDecimal($value, $label);
    }
}
