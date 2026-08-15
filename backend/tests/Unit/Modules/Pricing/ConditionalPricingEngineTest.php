<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Modules\Pricing\Models\PriceListConditionalBand;
use App\Modules\Pricing\Models\PriceListConditionalRule;
use App\Modules\Pricing\Services\ConditionalPricingEngine;
use App\Modules\Pricing\Services\ConditionalPricingRuleEvaluator;
use App\Modules\Pricing\Services\ConditionalPricingScopeAggregator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

final class ConditionalPricingEngineTest extends TestCase
{
    public function test_monthly_driver_pipeline_aggregates_before_band_evaluation(): void
    {
        $rule = $this->ratioRule(
            rewardMethod: 'amount_per_unit',
            rewardQuantitySource: 'redirected_parcels',
        );

        $rule->setRelation(
            'bands',
            new Collection([
                $this->band(
                    1,
                    '30.0000',
                    '40.0000',
                    true,
                    false,
                    '1.5000',
                ),
                $this->band(
                    2,
                    '40.0000',
                    null,
                    true,
                    false,
                    '3.0000',
                ),
            ]),
        );

        $result = $this->engine()->evaluateRule(
            $rule,
            [
                $this->snapshot(
                    4,
                    '2026-08-01',
                    60,
                    30,
                ),
                $this->snapshot(
                    4,
                    '2026-08-02',
                    40,
                    15,
                ),
            ],
        );

        self::assertSame(
            '2026-08',
            $result['aggregate']['period'],
        );

        self::assertSame(
            2,
            $result['aggregate']['route_count'],
        );

        self::assertSame(
            '45.000000',
            $result['aggregate']['metric_numerator_value'],
        );

        self::assertSame(
            '100.000000',
            $result['aggregate']['metric_denominator_value'],
        );

        self::assertSame(
            '45.000000',
            $result['evaluation']['metric_value'],
        );

        self::assertSame(
            2,
            $result['evaluation']['matched_band_position'],
        );

        self::assertSame(
            '135.00',
            $result['evaluation']['conditional_amount'],
        );
    }

    public function test_per_route_quantity_pipeline_remains_route_scoped(): void
    {
        $rule = new PriceListConditionalRule;

        $rule->forceFill([
            'metric_type' => 'quantity',
            'metric_numerator_source' => 'redirected_parcels',
            'metric_denominator_source' => null,
            'evaluation_scope' => 'per_route',
            'reward_method' => 'fixed_amount',
            'reward_quantity_source' => null,
            'reward_target_item_code' => null,
            'rounding_scale' => 2,
            'rounding_method' => 'half_up',
        ]);

        $rule->setRelation(
            'bands',
            new Collection([
                $this->band(
                    1,
                    '10.0000',
                    null,
                    true,
                    false,
                    '12.3450',
                ),
            ]),
        );

        $result = $this->engine()->evaluateRule(
            $rule,
            [
                $this->snapshot(
                    4,
                    '2026-08-03',
                    100,
                    10,
                ),
            ],
        );

        self::assertSame(
            '2026-08-03',
            $result['aggregate']['period'],
        );

        self::assertSame(
            1,
            $result['aggregate']['route_count'],
        );

        self::assertSame(
            '10.000000',
            $result['evaluation']['metric_value'],
        );

        self::assertSame(
            '12.35',
            $result['evaluation']['conditional_amount'],
        );
    }

    public function test_percentage_of_item_base_amount_is_forwarded_through_engine(): void
    {
        $rule = $this->ratioRule(
            rewardMethod: 'percentage_of_item',
            rewardQuantitySource: null,
        );

        $rule->forceFill([
            'reward_target_item_code' => 'delivered_parcels',
        ]);

        $rule->setRelation(
            'bands',
            new Collection([
                $this->band(
                    1,
                    '30.0000',
                    null,
                    true,
                    false,
                    '5.0000',
                ),
            ]),
        );

        $result = $this->engine()->evaluateRule(
            $rule,
            [
                $this->snapshot(
                    4,
                    '2026-08-04',
                    100,
                    35,
                ),
            ],
            [
                'delivered_parcels' => '1000.00',
            ],
        );

        self::assertSame(
            '35.000000',
            $result['evaluation']['metric_value'],
        );

        self::assertSame(
            'delivered_parcels',
            $result['evaluation']['reward_target_item_code'],
        );

        self::assertSame(
            '1000',
            $result['evaluation']['reward_target_item_amount'],
        );

        self::assertSame(
            '50.00',
            $result['evaluation']['conditional_amount'],
        );
    }

    private function engine(): ConditionalPricingEngine
    {
        return new ConditionalPricingEngine(
            new ConditionalPricingScopeAggregator,
            new ConditionalPricingRuleEvaluator,
        );
    }

    private function ratioRule(
        string $rewardMethod,
        ?string $rewardQuantitySource,
    ): PriceListConditionalRule {
        $rule = new PriceListConditionalRule;

        $rule->forceFill([
            'metric_type' => 'ratio_percentage',
            'metric_numerator_source' => 'redirected_parcels',
            'metric_denominator_source' => 'loaded_parcels',
            'evaluation_scope' => 'monthly_driver',
            'reward_method' => $rewardMethod,
            'reward_quantity_source' => $rewardQuantitySource,
            'reward_target_item_code' => null,
            'rounding_scale' => 2,
            'rounding_method' => 'half_up',
        ]);

        return $rule;
    }

    private function band(
        int $position,
        ?string $minimum,
        ?string $maximum,
        bool $minimumInclusive,
        bool $maximumInclusive,
        string $adjustment,
    ): PriceListConditionalBand {
        $band = new PriceListConditionalBand;

        $band->forceFill([
            'minimum_value' => $minimum,
            'maximum_value' => $maximum,
            'minimum_inclusive' => $minimumInclusive,
            'maximum_inclusive' => $maximumInclusive,
            'adjustment_value' => $adjustment,
            'position' => $position,
        ]);

        return $band;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(
        int $driver,
        string $date,
        int $loaded,
        int $redirected,
    ): array {
        return [
            'performed_by_driver_id' => $driver,
            'service_date' => $date,
            'loaded_parcels' => $loaded,
            'delivered_parcels' => $loaded - $redirected,
            'redirected_parcels' => $redirected,
            'undelivered_parcels' => 0,
            'customer_rejected_parcels' => 0,
            'not_delivered_parcels' => 0,
            'processed_parcels' => $loaded,
            'planned_km' => '0.00',
            'actual_km' => '0.00',
        ];
    }
}
