<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Core\Organizations\OrganizationContext;
use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Services\ConditionalPricingEngine;
use App\Modules\Pricing\Services\ConditionalPricingRuleEvaluator;
use App\Modules\Pricing\Services\ConditionalPricingScopeAggregator;
use App\Modules\Pricing\Services\FinancialConditionalAdjustmentPersistenceService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

final class FinancialConditionalAdjustmentPersistenceServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        self::assertSame(
            'sqlite',
            DB::connection()->getDriverName(),
            'This test must never use persistent PostgreSQL.',
        );

        $this->organizationContext()->clear();
        Schema::dropAllTables();
        $this->createIsolatedSchema();
        $this->organizationContext()->set(10);
    }

    protected function tearDown(): void
    {
        $this->organizationContext()->clear();
        Schema::dropAllTables();
        parent::tearDown();
    }

    public function test_monthly_driver_adjustment_is_stored_once_with_ordered_sources(): void
    {
        [
            'rule_id' => $ruleId,
            'band_ids' => $bandIds,
        ] = $this->createRule(
            scope: 'monthly_driver',
            rewardMethod: 'amount_per_unit',
            rewardQuantitySource: 'redirected_parcels',
            rewardTargetItemCode: null,
            bands: [
                [
                    'minimum' => '30.0000',
                    'maximum' => '40.0000',
                    'minimum_inclusive' => true,
                    'maximum_inclusive' => false,
                    'adjustment' => '1.5000',
                ],
                [
                    'minimum' => '40.0000',
                    'maximum' => null,
                    'minimum_inclusive' => true,
                    'maximum_inclusive' => false,
                    'adjustment' => '3.0000',
                ],
            ],
        );

        $first = $this->createFinancialSource(
            serviceDate: '2026-08-01',
            loaded: 60,
            redirected: 30,
        );

        $second = $this->createFinancialSource(
            serviceDate: '2026-08-02',
            loaded: 40,
            redirected: 15,
        );

        $adjustment = $this->service()->createInitialAdjustment(
            conditionalRuleId: $ruleId,
            sourceFinancialCalculationIds: [$first, $second],
            calculatedByUserId: 7,
            calculatedAt: CarbonImmutable::parse(
                '2026-09-01 08:00:00',
                'Europe/Prague',
            ),
        );

        self::assertSame(
            'monthly_driver',
            $adjustment->getAttribute('evaluation_scope'),
        );
        self::assertSame(
            4,
            $adjustment->getAttribute('performed_by_driver_id'),
        );
        self::assertSame(
            '2026-08-01',
            $adjustment->period_start->format('Y-m-d'),
        );
        self::assertSame(
            '2026-08-31',
            $adjustment->period_end->format('Y-m-d'),
        );
        self::assertSame(
            '45.000000',
            $adjustment->getAttribute('metric_numerator_value'),
        );
        self::assertSame(
            '100.000000',
            $adjustment->getAttribute('metric_denominator_value'),
        );
        self::assertSame(
            '45.000000',
            $adjustment->getAttribute('metric_value'),
        );
        self::assertSame(
            '45.000000',
            $adjustment->getAttribute('reward_quantity_value'),
        );
        self::assertSame(
            '3.0000',
            $adjustment->getAttribute('adjustment_value'),
        );
        self::assertSame(
            '135.00',
            $adjustment->getAttribute('conditional_amount'),
        );
        self::assertSame(
            $bandIds[1],
            $adjustment->getAttribute('price_list_conditional_band_id'),
        );
        self::assertSame(
            [$first, $second],
            $adjustment->sources->pluck('financial_calculation_id')->all(),
        );
        self::assertSame(
            [1, 2],
            $adjustment->sources->pluck('source_position')->all(),
        );

        $snapshot = $adjustment->getAttribute('evaluation_snapshot');
        self::assertIsArray($snapshot);
        self::assertSame(
            [$first, $second],
            $snapshot['source_financial_calculation_ids'],
        );
        self::assertSame(
            '2026-08',
            $snapshot['aggregate']['period'],
        );
        self::assertSame(
            [
                'start' => '2026-08-01',
                'end' => '2026-08-31',
            ],
            $snapshot['evaluation_period'],
        );
        self::assertSame(
            1,
            DB::table('financial_conditional_adjustments')->count(),
        );
        self::assertSame(
            2,
            DB::table('financial_conditional_adjustment_sources')->count(),
        );
    }

    public function test_monthly_period_is_clipped_by_price_list_version_start(): void
    {
        ['rule_id' => $ruleId] = $this->createRule(
            scope: 'monthly_driver',
            rewardMethod: 'amount_per_unit',
            rewardQuantitySource: 'redirected_parcels',
            rewardTargetItemCode: null,
            bands: [[
                'minimum' => '0.0000',
                'maximum' => null,
                'minimum_inclusive' => true,
                'maximum_inclusive' => false,
                'adjustment' => '1.0000',
            ]],
            validFrom: '2026-02-11',
            validUntil: '2026-07-31',
        );

        $first = $this->createFinancialSource(
            serviceDate: '2026-02-11',
            loaded: 100,
            redirected: 10,
        );

        $second = $this->createFinancialSource(
            serviceDate: '2026-02-20',
            loaded: 100,
            redirected: 20,
        );

        $adjustment = $this->service()->createInitialAdjustment(
            conditionalRuleId: $ruleId,
            sourceFinancialCalculationIds: [$first, $second],
            calculatedByUserId: 7,
            calculatedAt: CarbonImmutable::parse(
                '2026-03-01 08:00:00',
            ),
        );

        self::assertSame(
            '2026-02-11',
            $adjustment->period_start->format('Y-m-d'),
        );
        self::assertSame(
            '2026-02-28',
            $adjustment->period_end->format('Y-m-d'),
        );

        $snapshot = $adjustment->getAttribute(
            'evaluation_snapshot',
        );

        self::assertSame(
            [
                'start' => '2026-02-11',
                'end' => '2026-02-28',
            ],
            $snapshot['evaluation_period'],
        );
    }

    public function test_monthly_period_is_clipped_by_price_list_version_end(): void
    {
        ['rule_id' => $ruleId] = $this->createRule(
            scope: 'monthly_driver',
            rewardMethod: 'amount_per_unit',
            rewardQuantitySource: 'redirected_parcels',
            rewardTargetItemCode: null,
            bands: [[
                'minimum' => '0.0000',
                'maximum' => null,
                'minimum_inclusive' => true,
                'maximum_inclusive' => false,
                'adjustment' => '1.0000',
            ]],
            validFrom: '2025-11-01',
            validUntil: '2026-02-10',
        );

        $first = $this->createFinancialSource(
            serviceDate: '2026-02-01',
            loaded: 100,
            redirected: 10,
        );

        $second = $this->createFinancialSource(
            serviceDate: '2026-02-10',
            loaded: 100,
            redirected: 20,
        );

        $adjustment = $this->service()->createInitialAdjustment(
            conditionalRuleId: $ruleId,
            sourceFinancialCalculationIds: [$first, $second],
            calculatedByUserId: 7,
            calculatedAt: CarbonImmutable::parse(
                '2026-03-01 08:00:00',
            ),
        );

        self::assertSame(
            '2026-02-01',
            $adjustment->period_start->format('Y-m-d'),
        );
        self::assertSame(
            '2026-02-10',
            $adjustment->period_end->format('Y-m-d'),
        );
    }

    public function test_source_outside_price_list_version_period_is_blocked_atomically(): void
    {
        ['rule_id' => $ruleId] = $this->createRule(
            scope: 'monthly_driver',
            rewardMethod: 'amount_per_unit',
            rewardQuantitySource: 'redirected_parcels',
            rewardTargetItemCode: null,
            bands: [[
                'minimum' => '0.0000',
                'maximum' => null,
                'minimum_inclusive' => true,
                'maximum_inclusive' => false,
                'adjustment' => '1.0000',
            ]],
            validFrom: '2026-02-11',
            validUntil: '2026-07-31',
        );

        $invalid = $this->createFinancialSource(
            serviceDate: '2026-02-10',
            loaded: 100,
            redirected: 20,
        );

        try {
            $this->service()->createInitialAdjustment(
                conditionalRuleId: $ruleId,
                sourceFinancialCalculationIds: [$invalid],
                calculatedByUserId: 7,
                calculatedAt: CarbonImmutable::parse(
                    '2026-03-01 08:00:00',
                ),
            );

            self::fail(
                'A source outside the price-list version period was not blocked.',
            );
        } catch (DomainException) {
            self::assertSame(
                0,
                DB::table(
                    'financial_conditional_adjustments',
                )->count(),
            );
            self::assertSame(
                0,
                DB::table(
                    'financial_conditional_adjustment_sources',
                )->count(),
            );
        }
    }

    public function test_percentage_of_item_base_amount_is_derived_from_source_lines(): void
    {
        ['rule_id' => $ruleId] = $this->createRule(
            scope: 'per_route',
            rewardMethod: 'percentage_of_item',
            rewardQuantitySource: null,
            rewardTargetItemCode: 'delivered_parcels',
            bands: [
                [
                    'minimum' => '30.0000',
                    'maximum' => null,
                    'minimum_inclusive' => true,
                    'maximum_inclusive' => false,
                    'adjustment' => '5.0000',
                ],
            ],
        );

        $source = $this->createFinancialSource(
            serviceDate: '2026-08-03',
            loaded: 100,
            redirected: 35,
            lineAmounts: ['delivered_parcels' => '1000.00'],
        );

        $adjustment = $this->service()->createInitialAdjustment(
            conditionalRuleId: $ruleId,
            sourceFinancialCalculationIds: [$source],
            calculatedByUserId: 7,
            calculatedAt: CarbonImmutable::parse(
                '2026-08-03 20:00:00',
                'Europe/Prague',
            ),
        );

        self::assertSame(
            'per_route',
            $adjustment->getAttribute('evaluation_scope'),
        );
        self::assertSame(
            '2026-08-03',
            $adjustment->period_start->format('Y-m-d'),
        );
        self::assertSame(
            '1000.00',
            $adjustment->getAttribute('reward_target_item_amount'),
        );
        self::assertSame(
            '50.00',
            $adjustment->getAttribute('conditional_amount'),
        );
        self::assertSame(1, $adjustment->sources->count());
    }

    public function test_scope_cannot_mix_financial_organizations(): void
    {
        ['rule_id' => $ruleId] = $this->createRule(
            scope: 'monthly_driver',
            rewardMethod: 'amount_per_unit',
            rewardQuantitySource: 'redirected_parcels',
            rewardTargetItemCode: null,
            bands: [[
                'minimum' => '0.0000',
                'maximum' => null,
                'minimum_inclusive' => true,
                'maximum_inclusive' => false,
                'adjustment' => '1.0000',
            ]],
        );

        $first = $this->createFinancialSource(
            serviceDate: '2026-08-04',
            loaded: 100,
            redirected: 20,
            organizationId: 10,
        );

        $second = $this->createFinancialSource(
            serviceDate: '2026-08-05',
            loaded: 100,
            redirected: 20,
            organizationId: 11,
        );

        try {
            $this->service()->createInitialAdjustment(
                conditionalRuleId: $ruleId,
                sourceFinancialCalculationIds: [$first, $second],
                calculatedByUserId: 7,
                calculatedAt: CarbonImmutable::parse('2026-09-01'),
            );
            self::fail('Mixed organizations were not blocked.');
        } catch (DomainException) {
            self::assertSame(
                0,
                DB::table('financial_conditional_adjustments')->count(),
            );
            self::assertSame(
                0,
                DB::table('financial_conditional_adjustment_sources')->count(),
            );
        }
    }

    public function test_non_final_financial_source_is_blocked_atomically(): void
    {
        ['rule_id' => $ruleId] = $this->createRule(
            scope: 'per_route',
            rewardMethod: 'amount_per_unit',
            rewardQuantitySource: 'redirected_parcels',
            rewardTargetItemCode: null,
            bands: [[
                'minimum' => '0.0000',
                'maximum' => null,
                'minimum_inclusive' => true,
                'maximum_inclusive' => false,
                'adjustment' => '1.0000',
            ]],
        );

        $source = $this->createFinancialSource(
            serviceDate: '2026-08-06',
            loaded: 100,
            redirected: 20,
            financialStatus: FinancialCalculation::STATUS_CALCULATED,
        );

        try {
            $this->service()->createInitialAdjustment(
                conditionalRuleId: $ruleId,
                sourceFinancialCalculationIds: [$source],
                calculatedByUserId: 7,
                calculatedAt: CarbonImmutable::parse('2026-08-06 20:00:00'),
            );
            self::fail('A non-final financial source was not blocked.');
        } catch (DomainException) {
            self::assertSame(
                0,
                DB::table('financial_conditional_adjustments')->count(),
            );
            self::assertSame(
                0,
                DB::table('financial_conditional_adjustment_sources')->count(),
            );
        }
    }

    public function test_duplicate_initial_scope_is_blocked(): void
    {
        ['rule_id' => $ruleId] = $this->createRule(
            scope: 'monthly_driver',
            rewardMethod: 'amount_per_unit',
            rewardQuantitySource: 'redirected_parcels',
            rewardTargetItemCode: null,
            bands: [[
                'minimum' => '0.0000',
                'maximum' => null,
                'minimum_inclusive' => true,
                'maximum_inclusive' => false,
                'adjustment' => '1.0000',
            ]],
        );

        $source = $this->createFinancialSource(
            serviceDate: '2026-08-07',
            loaded: 100,
            redirected: 20,
        );

        $this->service()->createInitialAdjustment(
            conditionalRuleId: $ruleId,
            sourceFinancialCalculationIds: [$source],
            calculatedByUserId: 7,
            calculatedAt: CarbonImmutable::parse('2026-09-01 08:00:00'),
        );

        try {
            $this->service()->createInitialAdjustment(
                conditionalRuleId: $ruleId,
                sourceFinancialCalculationIds: [$source],
                calculatedByUserId: 7,
                calculatedAt: CarbonImmutable::parse('2026-09-01 09:00:00'),
            );
            self::fail('Duplicate initial scope was not blocked.');
        } catch (DomainException) {
            self::assertSame(
                1,
                DB::table('financial_conditional_adjustments')->count(),
            );
            self::assertSame(
                1,
                DB::table('financial_conditional_adjustment_sources')->count(),
            );
        }
    }

    public function test_recalculation_creates_immutable_v2_with_new_source_membership(): void
    {
        ['rule_id' => $ruleId] = $this->createRule(
            scope: 'monthly_driver',
            rewardMethod: 'amount_per_unit',
            rewardQuantitySource: 'redirected_parcels',
            rewardTargetItemCode: null,
            bands: [[
                'minimum' => '0.0000',
                'maximum' => null,
                'minimum_inclusive' => true,
                'maximum_inclusive' => false,
                'adjustment' => '1.0000',
            ]],
        );

        $first = $this->createFinancialSource(
            serviceDate: '2026-08-08',
            loaded: 100,
            redirected: 20,
        );

        $second = $this->createFinancialSource(
            serviceDate: '2026-08-09',
            loaded: 100,
            redirected: 30,
        );

        $v1 = $this->service()->createInitialAdjustment(
            conditionalRuleId: $ruleId,
            sourceFinancialCalculationIds: [$first],
            calculatedByUserId: 7,
            calculatedAt: CarbonImmutable::parse(
                '2026-09-01 08:00:00',
            ),
        );

        $v1Id = (int) $v1->getKey();
        $v1PublicId = (string) $v1->getAttribute('public_id');

        $v2 = $this->service()->createRecalculatedAdjustment(
            supersedesAdjustmentId: $v1Id,
            sourceFinancialCalculationIds: [$first, $second],
            calculatedByUserId: 7,
            calculatedAt: CarbonImmutable::parse(
                '2026-09-02 08:00:00',
            ),
        );

        self::assertSame(
            2,
            $v2->getAttribute('calculation_version'),
        );
        self::assertSame(
            $v1Id,
            $v2->getAttribute('supersedes_adjustment_id'),
        );
        self::assertSame(
            '50.00',
            $v2->getAttribute('conditional_amount'),
        );
        self::assertSame(
            [$first, $second],
            $v2->sources
                ->pluck('financial_calculation_id')
                ->all(),
        );

        $v1->refresh();
        $v1->load('sources');

        self::assertSame(
            1,
            $v1->getAttribute('calculation_version'),
        );
        self::assertNull(
            $v1->getAttribute('supersedes_adjustment_id'),
        );
        self::assertSame(
            '20.00',
            $v1->getAttribute('conditional_amount'),
        );
        self::assertSame(
            [$first],
            $v1->sources
                ->pluck('financial_calculation_id')
                ->all(),
        );
        self::assertSame(
            $v1PublicId,
            $v1->getAttribute('public_id'),
        );

        self::assertSame(
            1,
            $v1->supersededByAdjustments()->count(),
        );
        self::assertSame(
            $v1Id,
            $v2->supersedesAdjustment->getKey(),
        );

        $snapshot = $v2->getAttribute(
            'evaluation_snapshot',
        );

        self::assertIsArray($snapshot);
        self::assertSame(
            2,
            $snapshot['adjustment_lineage']['calculation_version'],
        );
        self::assertSame(
            $v1Id,
            $snapshot['adjustment_lineage']['supersedes_adjustment_id'],
        );
        self::assertSame(
            $v1PublicId,
            $snapshot['adjustment_lineage']['supersedes_adjustment_public_id'],
        );

        self::assertSame(
            2,
            DB::table('financial_conditional_adjustments')->count(),
        );
        self::assertSame(
            3,
            DB::table(
                'financial_conditional_adjustment_sources',
            )->count(),
        );
    }

    public function test_recalculation_chain_advances_v1_v2_v3(): void
    {
        ['rule_id' => $ruleId] = $this->createRule(
            scope: 'monthly_driver',
            rewardMethod: 'amount_per_unit',
            rewardQuantitySource: 'redirected_parcels',
            rewardTargetItemCode: null,
            bands: [[
                'minimum' => '0.0000',
                'maximum' => null,
                'minimum_inclusive' => true,
                'maximum_inclusive' => false,
                'adjustment' => '1.0000',
            ]],
        );

        $first = $this->createFinancialSource(
            serviceDate: '2026-08-10',
            loaded: 100,
            redirected: 10,
        );
        $second = $this->createFinancialSource(
            serviceDate: '2026-08-11',
            loaded: 100,
            redirected: 20,
        );
        $third = $this->createFinancialSource(
            serviceDate: '2026-08-12',
            loaded: 100,
            redirected: 30,
        );

        $v1 = $this->service()->createInitialAdjustment(
            conditionalRuleId: $ruleId,
            sourceFinancialCalculationIds: [$first],
            calculatedByUserId: 7,
            calculatedAt: CarbonImmutable::parse('2026-09-01 08:00:00'),
        );

        $v2 = $this->service()->createRecalculatedAdjustment(
            supersedesAdjustmentId: (int) $v1->getKey(),
            sourceFinancialCalculationIds: [$first, $second],
            calculatedByUserId: 7,
            calculatedAt: CarbonImmutable::parse('2026-09-02 08:00:00'),
        );

        $v3 = $this->service()->createRecalculatedAdjustment(
            supersedesAdjustmentId: (int) $v2->getKey(),
            sourceFinancialCalculationIds: [$first, $second, $third],
            calculatedByUserId: 7,
            calculatedAt: CarbonImmutable::parse('2026-09-03 08:00:00'),
        );

        self::assertSame(
            [1, 2, 3],
            DB::table('financial_conditional_adjustments')
                ->orderBy('calculation_version')
                ->pluck('calculation_version')
                ->all(),
        );

        self::assertSame(
            [
                null,
                (int) $v1->getKey(),
                (int) $v2->getKey(),
            ],
            DB::table('financial_conditional_adjustments')
                ->orderBy('calculation_version')
                ->pluck('supersedes_adjustment_id')
                ->all(),
        );

        self::assertSame(
            ['10.00', '30.00', '60.00'],
            DB::table('financial_conditional_adjustments')
                ->orderBy('calculation_version')
                ->pluck('conditional_amount')
                ->map(
                    static fn (mixed $value): string => number_format((float) $value, 2, '.', ''),
                )
                ->all(),
        );

        self::assertSame(
            1,
            DB::table('financial_conditional_adjustment_sources')
                ->where(
                    'financial_conditional_adjustment_id',
                    $v1->getKey(),
                )
                ->count(),
        );
        self::assertSame(
            2,
            DB::table('financial_conditional_adjustment_sources')
                ->where(
                    'financial_conditional_adjustment_id',
                    $v2->getKey(),
                )
                ->count(),
        );
        self::assertSame(
            3,
            DB::table('financial_conditional_adjustment_sources')
                ->where(
                    'financial_conditional_adjustment_id',
                    $v3->getKey(),
                )
                ->count(),
        );
    }

    public function test_recalculation_cannot_branch_from_superseded_version(): void
    {
        ['rule_id' => $ruleId] = $this->createRule(
            scope: 'monthly_driver',
            rewardMethod: 'amount_per_unit',
            rewardQuantitySource: 'redirected_parcels',
            rewardTargetItemCode: null,
            bands: [[
                'minimum' => '0.0000',
                'maximum' => null,
                'minimum_inclusive' => true,
                'maximum_inclusive' => false,
                'adjustment' => '1.0000',
            ]],
        );

        $first = $this->createFinancialSource(
            serviceDate: '2026-08-13',
            loaded: 100,
            redirected: 10,
        );
        $second = $this->createFinancialSource(
            serviceDate: '2026-08-14',
            loaded: 100,
            redirected: 20,
        );
        $third = $this->createFinancialSource(
            serviceDate: '2026-08-15',
            loaded: 100,
            redirected: 30,
        );

        $v1 = $this->service()->createInitialAdjustment(
            conditionalRuleId: $ruleId,
            sourceFinancialCalculationIds: [$first],
            calculatedByUserId: 7,
            calculatedAt: CarbonImmutable::parse('2026-09-01 08:00:00'),
        );

        $v2 = $this->service()->createRecalculatedAdjustment(
            supersedesAdjustmentId: (int) $v1->getKey(),
            sourceFinancialCalculationIds: [$first, $second],
            calculatedByUserId: 7,
            calculatedAt: CarbonImmutable::parse('2026-09-02 08:00:00'),
        );

        try {
            $this->service()->createRecalculatedAdjustment(
                supersedesAdjustmentId: (int) $v1->getKey(),
                sourceFinancialCalculationIds: [$first, $third],
                calculatedByUserId: 7,
                calculatedAt: CarbonImmutable::parse(
                    '2026-09-03 08:00:00',
                ),
            );

            self::fail(
                'A recalculation branch from an old version was not blocked.',
            );
        } catch (DomainException) {
            self::assertSame(
                2,
                DB::table('financial_conditional_adjustments')->count(),
            );
            self::assertSame(
                3,
                DB::table(
                    'financial_conditional_adjustment_sources',
                )->count(),
            );
            self::assertSame(
                (int) $v2->getKey(),
                DB::table('financial_conditional_adjustments')
                    ->where(
                        'supersedes_adjustment_id',
                        $v1->getKey(),
                    )
                    ->value('id'),
            );
        }
    }

    public function test_recalculation_must_preserve_evaluation_period(): void
    {
        ['rule_id' => $ruleId] = $this->createRule(
            scope: 'monthly_driver',
            rewardMethod: 'amount_per_unit',
            rewardQuantitySource: 'redirected_parcels',
            rewardTargetItemCode: null,
            bands: [[
                'minimum' => '0.0000',
                'maximum' => null,
                'minimum_inclusive' => true,
                'maximum_inclusive' => false,
                'adjustment' => '1.0000',
            ]],
        );

        $august = $this->createFinancialSource(
            serviceDate: '2026-08-16',
            loaded: 100,
            redirected: 20,
        );
        $september = $this->createFinancialSource(
            serviceDate: '2026-09-01',
            loaded: 100,
            redirected: 20,
        );

        $v1 = $this->service()->createInitialAdjustment(
            conditionalRuleId: $ruleId,
            sourceFinancialCalculationIds: [$august],
            calculatedByUserId: 7,
            calculatedAt: CarbonImmutable::parse(
                '2026-09-01 08:00:00',
            ),
        );

        try {
            $this->service()->createRecalculatedAdjustment(
                supersedesAdjustmentId: (int) $v1->getKey(),
                sourceFinancialCalculationIds: [$september],
                calculatedByUserId: 7,
                calculatedAt: CarbonImmutable::parse(
                    '2026-10-01 08:00:00',
                ),
            );

            self::fail(
                'A recalculation period change was not blocked.',
            );
        } catch (DomainException) {
            self::assertSame(
                1,
                DB::table('financial_conditional_adjustments')->count(),
            );
            self::assertSame(
                1,
                DB::table(
                    'financial_conditional_adjustment_sources',
                )->count(),
            );
        }
    }

    private function service(): FinancialConditionalAdjustmentPersistenceService
    {
        return new FinancialConditionalAdjustmentPersistenceService(
            $this->organizationContext(),
            new ConditionalPricingEngine(
                new ConditionalPricingScopeAggregator,
                new ConditionalPricingRuleEvaluator,
            ),
        );
    }

    private function organizationContext(): OrganizationContext
    {
        return app(OrganizationContext::class);
    }

    /**
     * @param list<array{
     *     minimum:string|null,
     *     maximum:string|null,
     *     minimum_inclusive:bool,
     *     maximum_inclusive:bool,
     *     adjustment:string
     * }> $bands
     * @return array{rule_id:int,band_ids:list<int>}
     */
    private function createRule(
        string $scope,
        string $rewardMethod,
        ?string $rewardQuantitySource,
        ?string $rewardTargetItemCode,
        array $bands,
        string $validFrom = '2026-08-01',
        ?string $validUntil = null,
    ): array {
        DB::table('price_list_versions')->updateOrInsert(
            ['id' => 200],
            [
                'price_list_id' => 100,
                'status' => 'active',
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
            ],
        );

        $ruleId = DB::table('price_list_conditional_rules')->insertGetId([
            'price_list_version_id' => 200,
            'code' => 'test-rule-'.Str::uuid(),
            'name' => 'Test conditional rule',
            'description' => null,
            'metric_type' => 'ratio_percentage',
            'metric_numerator_source' => 'redirected_parcels',
            'metric_denominator_source' => 'loaded_parcels',
            'evaluation_scope' => $scope,
            'reward_method' => $rewardMethod,
            'reward_quantity_source' => $rewardQuantitySource,
            'reward_target_item_code' => $rewardTargetItemCode,
            'rounding_scale' => 2,
            'rounding_method' => 'half_up',
            'position' => 1,
            'created_at' => '2026-08-13 00:00:00',
        ]);

        $bandIds = [];

        foreach ($bands as $index => $band) {
            $bandIds[] = DB::table('price_list_conditional_bands')->insertGetId([
                'price_list_conditional_rule_id' => $ruleId,
                'minimum_value' => $band['minimum'],
                'maximum_value' => $band['maximum'],
                'minimum_inclusive' => $band['minimum_inclusive'],
                'maximum_inclusive' => $band['maximum_inclusive'],
                'adjustment_value' => $band['adjustment'],
                'position' => $index + 1,
                'created_at' => '2026-08-13 00:00:00',
            ]);
        }

        return ['rule_id' => $ruleId, 'band_ids' => $bandIds];
    }

    /**
     * @param  array<string, string>  $lineAmounts
     */
    private function createFinancialSource(
        string $serviceDate,
        int $loaded,
        int $redirected,
        int $organizationId = 10,
        string $financialStatus = FinancialCalculation::STATUS_APPROVED,
        array $lineAmounts = [],
    ): int {
        $existing = DB::table('financial_calculations')->count();
        $dailyReportId = 1000 + $existing + 1;

        $snapshot = [
            'daily_report_id' => $dailyReportId,
            'daily_report_version' => 1,
            'public_id' => (string) Str::uuid(),
            'organization_id' => 1,
            'trip_id' => null,
            'performed_by_driver_id' => 4,
            'vehicle_id' => null,
            'route_number' => (string) $dailyReportId,
            'route_number_normalized' => (string) $dailyReportId,
            'service_date' => $serviceDate,
            'status' => 'approved',
            'loaded_parcels' => $loaded,
            'delivered_parcels' => $loaded - $redirected,
            'redirected_parcels' => $redirected,
            'undelivered_parcels' => 0,
            'customer_rejected_parcels' => 0,
            'not_delivered_parcels' => 0,
            'processed_parcels' => $loaded,
            'planned_km' => '0.00',
            'actual_km' => '0.00',
            'actual_km_source' => null,
            'approved_at' => $serviceDate.' 20:00:00',
            'approved_by_user_id' => 7,
            'closed_at' => null,
            'captured_at' => $serviceDate.' 20:05:00',
        ];

        $id = DB::table('financial_calculations')->insertGetId([
            'public_id' => (string) Str::uuid(),
            'organization_id' => $organizationId,
            'organization_relationship_id' => 20,
            'price_list_id' => 100,
            'price_list_version_id' => 200,
            'daily_report_id' => $dailyReportId,
            'daily_report_version' => 1,
            'calculation_version' => 1,
            'status' => $financialStatus,
            'currency' => 'CZK',
            'input_snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
            'subtotal_amount' => '0.00',
            'total_amount' => '0.00',
            'created_at' => '2026-08-13 00:00:00',
            'updated_at' => '2026-08-13 00:00:00',
        ]);

        $position = 1;
        foreach ($lineAmounts as $pricingCode => $amount) {
            DB::table('financial_calculation_lines')->insert([
                'financial_calculation_id' => $id,
                'pricing_code' => $pricingCode,
                'line_amount' => $amount,
                'position' => $position,
            ]);
            $position++;
        }

        return $id;
    }

    private function createIsolatedSchema(): void
    {
        Schema::create('price_list_versions', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('price_list_id');
            $table->string('status');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
        });

        Schema::create('price_list_conditional_rules', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('price_list_version_id');
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('metric_type');
            $table->string('metric_numerator_source');
            $table->string('metric_denominator_source')->nullable();
            $table->string('evaluation_scope');
            $table->string('reward_method');
            $table->string('reward_quantity_source')->nullable();
            $table->string('reward_target_item_code')->nullable();
            $table->unsignedSmallInteger('rounding_scale')->default(2);
            $table->string('rounding_method')->default('half_up');
            $table->unsignedSmallInteger('position');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('price_list_conditional_bands', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('price_list_conditional_rule_id');
            $table->decimal('minimum_value', 18, 4)->nullable();
            $table->decimal('maximum_value', 18, 4)->nullable();
            $table->boolean('minimum_inclusive')->default(true);
            $table->boolean('maximum_inclusive')->default(false);
            $table->decimal('adjustment_value', 16, 4);
            $table->unsignedSmallInteger('position');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('financial_calculations', static function (Blueprint $table): void {
            $table->id();
            $table->string('public_id');
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('organization_relationship_id');
            $table->unsignedBigInteger('price_list_id');
            $table->unsignedBigInteger('price_list_version_id');
            $table->unsignedBigInteger('daily_report_id');
            $table->unsignedInteger('daily_report_version');
            $table->unsignedInteger('calculation_version');
            $table->string('status');
            $table->char('currency', 3);
            $table->json('input_snapshot');
            $table->decimal('subtotal_amount', 16, 2);
            $table->decimal('total_amount', 16, 2);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('financial_calculation_lines', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('financial_calculation_id');
            $table->string('pricing_code');
            $table->decimal('line_amount', 16, 2);
            $table->unsignedSmallInteger('position');
        });

        Schema::create('financial_conditional_adjustments', static function (Blueprint $table): void {
            $table->id();
            $table->string('public_id')->unique();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('organization_relationship_id');
            $table->unsignedBigInteger('price_list_id');
            $table->unsignedBigInteger('price_list_version_id');
            $table->unsignedBigInteger('price_list_conditional_rule_id');
            $table->unsignedBigInteger('price_list_conditional_band_id')->nullable();
            $table->unsignedBigInteger('performed_by_driver_id');
            $table->string('evaluation_scope');
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('calculation_version')->default(1);
            $table->char('currency', 3);
            $table->string('metric_type');
            $table->string('metric_numerator_source');
            $table->decimal('metric_numerator_value', 18, 6);
            $table->string('metric_denominator_source')->nullable();
            $table->decimal('metric_denominator_value', 18, 6)->nullable();
            $table->decimal('metric_value', 18, 6);
            $table->string('reward_method');
            $table->string('reward_quantity_source')->nullable();
            $table->decimal('reward_quantity_value', 18, 6)->nullable();
            $table->string('reward_target_item_code')->nullable();
            $table->decimal('reward_target_item_amount', 16, 2)->nullable();
            $table->decimal('adjustment_value', 16, 4)->nullable();
            $table->decimal('conditional_amount', 16, 2);
            $table->json('evaluation_snapshot');
            $table->unsignedBigInteger('calculated_by_user_id');
            $table->timestamp('calculated_at');
            $table->unsignedBigInteger('supersedes_adjustment_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(
                [
                    'price_list_conditional_rule_id',
                    'performed_by_driver_id',
                    'period_start',
                    'period_end',
                    'calculation_version',
                ],
                'test_conditional_scope_unique',
            );

            $table->unique(
                'supersedes_adjustment_id',
                'test_conditional_supersedes_unique',
            );
        });

        Schema::create('financial_conditional_adjustment_sources', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('financial_conditional_adjustment_id');
            $table->unsignedBigInteger('financial_calculation_id');
            $table->unsignedSmallInteger('source_position');
            $table->timestamp('created_at')->nullable();

            $table->unique(
                [
                    'financial_conditional_adjustment_id',
                    'financial_calculation_id',
                ],
                'test_conditional_source_unique',
            );

            $table->unique(
                [
                    'financial_conditional_adjustment_id',
                    'source_position',
                ],
                'test_conditional_position_unique',
            );
        });
    }
}
