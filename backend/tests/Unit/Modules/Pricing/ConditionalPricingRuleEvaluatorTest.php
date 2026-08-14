<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Modules\Pricing\Models\PriceListConditionalBand;
use App\Modules\Pricing\Models\PriceListConditionalRule;
use App\Modules\Pricing\Services\ConditionalPricingRuleEvaluator;
use DomainException;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

final class ConditionalPricingRuleEvaluatorTest extends TestCase
{
    public function test_ratio_metric_selects_configured_band_and_amount_per_unit(): void
    {
        $rule = $this->ratioRule(
            rewardMethod: 'amount_per_unit',
        );

        $rule->setRelation(
            'bands',
            new Collection([
                $this->band(
                    position: 1,
                    minimum: '30.0000',
                    maximum: '40.0000',
                    minimumInclusive: true,
                    maximumInclusive: false,
                    adjustment: '1.5000',
                ),
                $this->band(
                    position: 2,
                    minimum: '40.0000',
                    maximum: null,
                    minimumInclusive: true,
                    maximumInclusive: false,
                    adjustment: '3.0000',
                ),
            ]),
        );

        $result = (new ConditionalPricingRuleEvaluator)->evaluate(
            $rule,
            $this->aggregate(
                numerator: '45.000000',
                denominator: '100.000000',
                rewardQuantity: '45.000000',
            ),
        );

        self::assertSame(
            '45.000000',
            $result['metric_value'],
        );

        self::assertSame(
            2,
            $result['matched_band_position'],
        );

        self::assertSame(
            '3',
            $result['adjustment_value'],
        );

        self::assertSame(
            '45',
            $result['reward_quantity_value'],
        );

        self::assertSame(
            '135.00',
            $result['conditional_amount'],
        );
    }

    public function test_band_boundary_semantics_are_taken_from_configuration(): void
    {
        $rule = $this->ratioRule(
            rewardMethod: 'fixed_amount',
            rewardQuantitySource: null,
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
                    '10.0000',
                ),
                $this->band(
                    2,
                    '40.0000',
                    null,
                    true,
                    false,
                    '20.0000',
                ),
            ]),
        );

        $result = (new ConditionalPricingRuleEvaluator)->evaluate(
            $rule,
            $this->aggregate(
                numerator: '40.000000',
                denominator: '100.000000',
                rewardQuantity: null,
            ),
        );

        self::assertSame(
            2,
            $result['matched_band_position'],
        );

        self::assertSame(
            '20.00',
            $result['conditional_amount'],
        );
    }

    public function test_no_matching_band_means_zero_conditional_amount(): void
    {
        $rule = $this->ratioRule(
            rewardMethod: 'fixed_amount',
            rewardQuantitySource: null,
        );

        $rule->setRelation(
            'bands',
            new Collection([
                $this->band(
                    1,
                    '30.0000',
                    null,
                    true,
                    false,
                    '50.0000',
                ),
            ]),
        );

        $result = (new ConditionalPricingRuleEvaluator)->evaluate(
            $rule,
            $this->aggregate(
                numerator: '20.000000',
                denominator: '100.000000',
                rewardQuantity: null,
            ),
        );

        self::assertNull(
            $result['matched_band_position'],
        );

        self::assertSame(
            '0.00',
            $result['conditional_amount'],
        );
    }

    public function test_fixed_amount_is_rounded_by_rule_configuration(): void
    {
        $rule = $this->quantityRule(
            rewardMethod: 'fixed_amount',
        );

        $rule->forceFill([
            'rounding_scale' => 2,
        ]);

        $rule->setRelation(
            'bands',
            new Collection([
                $this->band(
                    1,
                    '1.0000',
                    null,
                    true,
                    false,
                    '12.3450',
                ),
            ]),
        );

        $result = (new ConditionalPricingRuleEvaluator)->evaluate(
            $rule,
            [
                'evaluation_scope' => 'per_route',
                'driver_id' => 4,
                'period' => '2026-08-01',
                'route_count' => 1,
                'metric_numerator_source' => 'redirected_parcels',
                'metric_numerator_value' => '10.000000',
                'metric_denominator_source' => null,
                'metric_denominator_value' => null,
                'reward_quantity_source' => null,
                'reward_quantity_value' => null,
            ],
        );

        self::assertSame(
            '10.000000',
            $result['metric_value'],
        );

        self::assertSame(
            '12.35',
            $result['conditional_amount'],
        );
    }

    public function test_percentage_of_item_uses_explicit_target_item_amount(): void
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

        $result = (new ConditionalPricingRuleEvaluator)->evaluate(
            $rule,
            $this->aggregate(
                numerator: '35.000000',
                denominator: '100.000000',
                rewardQuantity: null,
            ),
            [
                'delivered_parcels' => '1000.00',
            ],
        );

        self::assertSame(
            'delivered_parcels',
            $result['reward_target_item_code'],
        );

        self::assertSame(
            '1000',
            $result['reward_target_item_amount'],
        );

        self::assertSame(
            '50.00',
            $result['conditional_amount'],
        );
    }

    public function test_zero_ratio_denominator_is_blocked_instead_of_assumed(): void
    {
        $rule = $this->ratioRule(
            rewardMethod: 'fixed_amount',
            rewardQuantitySource: null,
        );

        $rule->setRelation(
            'bands',
            new Collection([
                $this->band(
                    1,
                    '0.0000',
                    null,
                    true,
                    false,
                    '10.0000',
                ),
            ]),
        );

        $this->expectException(
            DomainException::class,
        );

        $this->expectExceptionMessage(
            'Conditional ratio metric cannot be evaluated with a zero denominator.',
        );

        (new ConditionalPricingRuleEvaluator)->evaluate(
            $rule,
            $this->aggregate(
                numerator: '0.000000',
                denominator: '0.000000',
                rewardQuantity: null,
            ),
        );
    }

    private function ratioRule(
        string $rewardMethod,
        ?string $rewardQuantitySource =
            'redirected_parcels',
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

    private function quantityRule(
        string $rewardMethod,
    ): PriceListConditionalRule {
        $rule = new PriceListConditionalRule;

        $rule->forceFill([
            'metric_type' => 'quantity',
            'metric_numerator_source' => 'redirected_parcels',
            'metric_denominator_source' => null,
            'evaluation_scope' => 'per_route',
            'reward_method' => $rewardMethod,
            'reward_quantity_source' => null,
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
    private function aggregate(
        string $numerator,
        ?string $denominator,
        ?string $rewardQuantity,
    ): array {
        return [
            'evaluation_scope' => 'monthly_driver',
            'driver_id' => 4,
            'period' => '2026-08',
            'route_count' => 2,
            'metric_numerator_source' => 'redirected_parcels',
            'metric_numerator_value' => $numerator,
            'metric_denominator_source' => 'loaded_parcels',
            'metric_denominator_value' => $denominator,
            'reward_quantity_source' => $rewardQuantity !== null
                    ? 'redirected_parcels'
                    : null,
            'reward_quantity_value' => $rewardQuantity,
        ];
    }
}
