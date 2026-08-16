<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Modules\Pricing\Models\PriceListConditionalRule;
use App\Modules\Pricing\Models\PriceListItem;
use App\Modules\Pricing\Requests\StoreProviderManagedPriceListRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class ConditionalPriceListRuleRequestValidationTest extends TestCase
{
    public function test_sources_may_repeat_across_independent_rules(): void
    {
        $validator = $this->validator([
            $this->rule(
                'delivery_quality',
                [
                    PriceListConditionalRule::SOURCE_DELIVERED_PARCELS,
                    PriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
                ],
                [PriceListConditionalRule::SOURCE_LOADED_PARCELS],
            ),
            $this->rule(
                'redirected_share',
                [PriceListConditionalRule::SOURCE_REDIRECTED_PARCELS],
                [PriceListConditionalRule::SOURCE_LOADED_PARCELS],
            ),
        ]);

        self::assertFalse(
            $validator->fails(),
            json_encode(
                $validator->errors()->toArray(),
                JSON_THROW_ON_ERROR,
            ),
        );
    }

    public function test_source_cannot_repeat_within_one_formula_role(): void
    {
        $validator = $this->validator([
            $this->rule(
                'redirected_share',
                [
                    PriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
                    PriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
                ],
                [PriceListConditionalRule::SOURCE_LOADED_PARCELS],
            ),
        ]);

        self::assertTrue($validator->fails());
        self::assertArrayHasKey(
            'conditional_rules.0.metric_numerator_sources',
            $validator->errors()->toArray(),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rules
     */
    private function validator(array $rules): ValidatorContract
    {
        $request = new StoreProviderManagedPriceListRequest;

        return Validator::make(
            [
                'name' => 'Shared source validation',
                'description' => null,
                'currency' => 'CZK',
                'valid_from' => '2026-08-16',
                'valid_until' => null,
                'change_reason' => null,
                'items' => $this->items(),
                'conditional_rules' => $rules,
            ],
            $request->rules(),
        );
    }

    /** @return list<array<string, mixed>> */
    private function items(): array
    {
        $items = [];

        foreach (PriceListItem::CODES as $code) {
            $items[] = [
                'code' => $code,
                'description' => null,
                'unit_rate' => '1.0000',
            ];
        }

        return $items;
    }

    /**
     * @param  list<string>  $numeratorSources
     * @param  list<string>  $denominatorSources
     * @return array<string, mixed>
     */
    private function rule(
        string $code,
        array $numeratorSources,
        array $denominatorSources,
    ): array {
        return [
            'code' => $code,
            'name' => $code,
            'description' => null,
            'metric_type' => PriceListConditionalRule::METRIC_TYPE_RATIO_PERCENTAGE,
            'metric_numerator_sources' => $numeratorSources,
            'metric_denominator_sources' => $denominatorSources,
            'evaluation_scope' => PriceListConditionalRule::EVALUATION_SCOPE_PER_ROUTE,
            'reward_method' => PriceListConditionalRule::REWARD_METHOD_FIXED_AMOUNT,
            'reward_quantity_source' => null,
            'reward_target_item_code' => null,
            'rounding_scale' => 2,
            'bands' => [
                [
                    'minimum_value' => '0.0000',
                    'maximum_value' => null,
                    'minimum_inclusive' => true,
                    'maximum_inclusive' => false,
                    'adjustment_value' => '1.0000',
                ],
            ],
        ];
    }
}
