<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Modules\Pricing\Models\DriverPriceListConditionalRule;
use App\Modules\Pricing\Models\DriverPriceListItem;
use App\Modules\Pricing\Requests\StoreDriverPriceListRequest;
use App\Modules\Pricing\Requests\StoreDriverPriceListVersionRequest;
use App\Modules\Pricing\Requests\UpdateDriverPriceListVersionRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class DriverConditionalPriceListRuleRequestValidationTest extends TestCase
{
    public function test_all_driver_write_requests_accept_shared_rule_contract(): void
    {
        foreach ($this->requestCases($this->redirectedRule()) as $case) {
            [$request, $payload] = $case;

            $validator = Validator::make(
                $payload,
                $request->rules(),
            );

            self::assertFalse(
                $validator->fails(),
                json_encode(
                    $validator->errors()->toArray(),
                    JSON_THROW_ON_ERROR,
                ),
            );
        }
    }

    public function test_all_driver_write_requests_reject_duplicate_formula_source(): void
    {
        $rule = $this->redirectedRule();

        $rule['metric_numerator_sources'] = [
            DriverPriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
            DriverPriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
        ];

        foreach ($this->requestCases($rule) as $case) {
            [$request, $payload] = $case;

            $validator = Validator::make(
                $payload,
                $request->rules(),
            );

            self::assertTrue($validator->fails());

            self::assertArrayHasKey(
                'conditional_rules.0.metric_numerator_sources',
                $validator->errors()->toArray(),
            );
        }
    }

    /**
     * @param  array<string, mixed>  $rule
     * @return list<array{
     *     StoreDriverPriceListRequest|StoreDriverPriceListVersionRequest|UpdateDriverPriceListVersionRequest,
     *     array<string, mixed>
     * }>
     */
    private function requestCases(array $rule): array
    {
        return [
            [
                new StoreDriverPriceListRequest,
                [
                    'driver_organization_assignment_id' => 1,
                    'code' => 'S025-DRIVER',
                    'name' => 'Driver compensation',
                    'description' => null,
                    'currency' => 'CZK',
                    'valid_from' => '2026-08-17',
                    'valid_until' => null,
                    'change_reason' => null,
                    'items' => $this->items(),
                    'conditional_rules' => [$rule],
                ],
            ],
            [
                new StoreDriverPriceListVersionRequest,
                [
                    'expected_current_version' => 1,
                    'valid_from' => '2026-09-01',
                    'valid_until' => null,
                    'change_reason' => 'New version',
                    'items' => $this->items(),
                    'conditional_rules' => [$rule],
                ],
            ],
            [
                new UpdateDriverPriceListVersionRequest,
                [
                    'name' => 'Updated driver compensation',
                    'description' => null,
                    'expected_lock_version' => 1,
                    'valid_from' => '2026-08-17',
                    'valid_until' => null,
                    'change_reason' => 'Updated rules',
                    'items' => $this->items(),
                    'conditional_rules' => [$rule],
                ],
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function items(): array
    {
        $items = [];

        foreach (DriverPriceListItem::CODES as $code) {
            $items[] = [
                'code' => $code,
                'description' => null,
                'unit_rate' => '1.0000',
            ];
        }

        return $items;
    }

    /** @return array<string, mixed> */
    private function redirectedRule(): array
    {
        return [
            'code' => 'redirected_share',
            'name' => 'Bonus za podíl přesměrovaných zásilek',
            'description' => null,
            'metric_type' => DriverPriceListConditionalRule::METRIC_TYPE_RATIO_PERCENTAGE,
            'metric_numerator_sources' => [
                DriverPriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
            ],
            'metric_denominator_sources' => [
                DriverPriceListConditionalRule::SOURCE_LOADED_PARCELS,
            ],
            'evaluation_scope' => DriverPriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_DRIVER,
            'reward_method' => DriverPriceListConditionalRule::REWARD_METHOD_AMOUNT_PER_UNIT,
            'reward_quantity_source' => DriverPriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
            'reward_target_item_code' => null,
            'rounding_scale' => 2,
            'bands' => [
                [
                    'minimum_value' => '30.0000',
                    'maximum_value' => '40.0000',
                    'minimum_inclusive' => true,
                    'maximum_inclusive' => false,
                    'adjustment_value' => '1.5000',
                ],
                [
                    'minimum_value' => '40.0000',
                    'maximum_value' => '100.0000',
                    'minimum_inclusive' => true,
                    'maximum_inclusive' => true,
                    'adjustment_value' => '3.0000',
                ],
            ],
        ];
    }
}
