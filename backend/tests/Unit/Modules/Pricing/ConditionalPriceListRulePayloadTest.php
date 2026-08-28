<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Modules\Pricing\Exceptions\InvalidConditionalRewardConfiguration;
use App\Modules\Pricing\Services\ConditionalPriceListRulePayload;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ConditionalPriceListRulePayloadTest extends TestCase
{
    public function test_multiple_formula_components_and_rules_are_preserved(): void
    {
        $rules = (new ConditionalPriceListRulePayload)->fromInput([
            'conditional_rules' => [
                $this->qualityRule(),
                $this->redirectedRule(),
            ],
        ]);

        self::assertCount(2, $rules);
        self::assertSame(
            [
                'delivered_parcels',
                'redirected_parcels',
                'customer_rejected_parcels',
            ],
            $rules[0]['metric_numerator_sources'],
        );
        self::assertSame(
            ['loaded_parcels'],
            $rules[0]['metric_denominator_sources'],
        );
        self::assertSame(
            'monthly_price_list',
            $rules[1]['evaluation_scope'],
        );
    }

    public function test_overlapping_bands_are_rejected_before_persistence(): void
    {
        $rule = $this->qualityRule();
        $rule['bands'][0]['maximum_inclusive'] = true;
        $rule['bands'][1]['minimum_inclusive'] = true;

        try {
            (new ConditionalPriceListRulePayload)->fromInput([
                'conditional_rules' => [$rule],
            ]);
            self::fail('Overlapping bands were accepted.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey(
                'conditional_rules.0.bands',
                $exception->errors(),
            );
        }
    }

    public function test_reward_method_shape_is_explicit(): void
    {
        $rule = $this->qualityRule();
        $rule['reward_method'] = 'fixed_amount';

        $this->expectException(
            InvalidConditionalRewardConfiguration::class,
        );

        (new ConditionalPriceListRulePayload)->fromInput([
            'conditional_rules' => [$rule],
        ]);
    }

    public function test_multiple_reward_quantity_sources_are_preserved(): void
    {
        $rules = (new ConditionalPriceListRulePayload)->fromInput([
            'conditional_rules' => [$this->qualityRule()],
        ]);

        self::assertSame(
            [
                'delivered_parcels',
                'redirected_parcels',
                'customer_rejected_parcels',
            ],
            $rules[0]['reward_quantity_sources'],
        );

        self::assertSame(
            'delivered_parcels',
            $rules[0]['reward_quantity_source'],
        );
    }

    public function test_legacy_reward_quantity_source_is_normalized(): void
    {
        $rule = $this->qualityRule();
        unset($rule['reward_quantity_sources']);
        $rule['reward_quantity_source'] = 'redirected_parcels';

        $rules = (new ConditionalPriceListRulePayload)->fromInput([
            'conditional_rules' => [$rule],
        ]);

        self::assertSame(
            ['redirected_parcels'],
            $rules[0]['reward_quantity_sources'],
        );
        self::assertSame(
            'redirected_parcels',
            $rules[0]['reward_quantity_source'],
        );
    }

    public function test_price_list_without_surcharge_is_valid(): void
    {
        self::assertSame(
            [],
            (new ConditionalPriceListRulePayload)->fromInput([
                'conditional_rules' => [],
            ]),
        );
    }

    public function test_zero_rate_is_a_valid_explicit_surcharge_value(): void
    {
        $rule = $this->qualityRule();
        $rule['bands'][0]['adjustment_value'] = '0';
        $rule['bands'] = [$rule['bands'][0]];

        $rules = (new ConditionalPriceListRulePayload)->fromInput([
            'conditional_rules' => [$rule],
        ]);

        self::assertSame('0', $rules[0]['bands'][0]['adjustment_value']);
    }

    /** @return array<string, mixed> */
    private function qualityRule(): array
    {
        return [
            'code' => 'delivery_quality',
            'name' => 'Delivery quality',
            'description' => null,
            'metric_type' => 'ratio_percentage',
            'metric_numerator_sources' => [
                'delivered_parcels',
                'redirected_parcels',
                'customer_rejected_parcels',
            ],
            'metric_denominator_sources' => [
                'loaded_parcels',
            ],
            'evaluation_scope' => 'per_route',
            'reward_method' => 'amount_per_unit',
            'reward_quantity_sources' => [
                'delivered_parcels',
                'redirected_parcels',
                'customer_rejected_parcels',
            ],
            'reward_quantity_source' => 'delivered_parcels',
            'reward_target_item_code' => null,
            'rounding_scale' => 2,
            'bands' => [
                [
                    'minimum_value' => '90.0000',
                    'maximum_value' => '95.0000',
                    'minimum_inclusive' => true,
                    'maximum_inclusive' => false,
                    'adjustment_value' => '1.5000',
                ],
                [
                    'minimum_value' => '95.0000',
                    'maximum_value' => '100.0000',
                    'minimum_inclusive' => true,
                    'maximum_inclusive' => true,
                    'adjustment_value' => '2.5000',
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function redirectedRule(): array
    {
        return [
            'code' => 'redirected_share',
            'name' => 'Redirected share',
            'description' => null,
            'metric_type' => 'ratio_percentage',
            'metric_numerator_sources' => [
                'redirected_parcels',
            ],
            'metric_denominator_sources' => [
                'loaded_parcels',
            ],
            'evaluation_scope' => 'monthly_price_list',
            'reward_method' => 'fixed_amount',
            'reward_quantity_sources' => [],
            'reward_quantity_source' => null,
            'reward_target_item_code' => null,
            'rounding_scale' => 2,
            'bands' => [
                [
                    'minimum_value' => '5.0000',
                    'maximum_value' => null,
                    'minimum_inclusive' => true,
                    'maximum_inclusive' => false,
                    'adjustment_value' => '500.0000',
                ],
            ],
        ];
    }
}
