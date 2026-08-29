<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Pricing\Models\DriverPriceList;
use App\Modules\Pricing\Models\DriverPriceListConditionalBand;
use App\Modules\Pricing\Models\DriverPriceListConditionalRule;
use App\Modules\Pricing\Models\DriverPriceListConditionalRuleMetricComponent;
use App\Modules\Pricing\Models\DriverPriceListConditionalRuleRewardComponent;
use App\Modules\Pricing\Models\DriverPriceListVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

final class DriverPriceListConditionalRulePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_conditional_schema_exposes_complete_rule_tree(): void
    {
        self::assertTrue(
            Schema::hasColumns(
                'driver_price_list_conditional_rules',
                [
                    'driver_price_list_version_id',
                    'code',
                    'name',
                    'description',
                    'metric_type',
                    'metric_numerator_source',
                    'metric_denominator_source',
                    'evaluation_scope',
                    'reward_method',
                    'reward_quantity_source',
                    'reward_target_item_code',
                    'rounding_scale',
                    'rounding_method',
                    'position',
                    'created_at',
                ],
            ),
        );

        self::assertTrue(
            Schema::hasColumns(
                'driver_price_list_conditional_rule_metric_components',
                [
                    'driver_price_list_conditional_rule_id',
                    'component_role',
                    'metric_source',
                    'position',
                    'created_at',
                ],
            ),
        );

        self::assertTrue(
            Schema::hasColumns(
                'driver_price_list_conditional_rule_reward_components',
                [
                    'driver_price_list_conditional_rule_id',
                    'metric_source',
                    'position',
                    'created_at',
                ],
            ),
        );

        self::assertTrue(
            Schema::hasColumns(
                'driver_price_list_conditional_bands',
                [
                    'driver_price_list_conditional_rule_id',
                    'minimum_value',
                    'maximum_value',
                    'minimum_inclusive',
                    'maximum_inclusive',
                    'adjustment_value',
                    'position',
                    'created_at',
                ],
            ),
        );
    }

    public function test_deleting_driver_version_cascades_complete_rule_tree(): void
    {
        $version = $this->createDriverPriceListVersion();

        $rule = $version->conditionalRules()->create([
            'code' => 'redirected_share',
            'name' => 'Bonus za podíl přesměrovaných zásilek',
            'description' => null,
            'metric_type' => DriverPriceListConditionalRule::METRIC_TYPE_RATIO_PERCENTAGE,
            'metric_numerator_source' => DriverPriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
            'metric_denominator_source' => DriverPriceListConditionalRule::SOURCE_LOADED_PARCELS,
            'evaluation_scope' => DriverPriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_DRIVER,
            'reward_method' => DriverPriceListConditionalRule::REWARD_METHOD_AMOUNT_PER_UNIT,
            'reward_quantity_source' => DriverPriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
            'reward_target_item_code' => null,
            'rounding_scale' => 2,
            'rounding_method' => DriverPriceListConditionalRule::ROUNDING_METHOD_HALF_UP,
            'position' => 1,
        ]);

        $rule->metricComponents()->createMany([
            [
                'component_role' => DriverPriceListConditionalRuleMetricComponent::ROLE_NUMERATOR,
                'metric_source' => DriverPriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
                'position' => 1,
            ],
            [
                'component_role' => DriverPriceListConditionalRuleMetricComponent::ROLE_DENOMINATOR,
                'metric_source' => DriverPriceListConditionalRule::SOURCE_LOADED_PARCELS,
                'position' => 1,
            ],
        ]);

        $rule->rewardComponents()->createMany([
            [
                'metric_source' => DriverPriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
                'position' => 1,
            ],
            [
                'metric_source' => DriverPriceListConditionalRule::SOURCE_DELIVERED_PARCELS,
                'position' => 2,
            ],
        ]);

        $rule->bands()->createMany([
            [
                'minimum_value' => '30.0000',
                'maximum_value' => '40.0000',
                'minimum_inclusive' => true,
                'maximum_inclusive' => false,
                'adjustment_value' => '1.5000',
                'position' => 1,
            ],
            [
                'minimum_value' => '40.0000',
                'maximum_value' => '100.0000',
                'minimum_inclusive' => true,
                'maximum_inclusive' => true,
                'adjustment_value' => '3.0000',
                'position' => 2,
            ],
        ]);

        $loadedRule = $version
            ->conditionalRules()
            ->with([
                'metricComponents',
                'rewardComponents',
                'bands',
            ])
            ->firstOrFail();

        self::assertSame(
            DriverPriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_DRIVER,
            $loadedRule->getAttribute('evaluation_scope'),
        );

        self::assertSame(
            [
                DriverPriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
            ],
            $loadedRule
                ->numeratorComponents()
                ->pluck('metric_source')
                ->all(),
        );

        self::assertSame(
            [
                DriverPriceListConditionalRule::SOURCE_LOADED_PARCELS,
            ],
            $loadedRule
                ->denominatorComponents()
                ->pluck('metric_source')
                ->all(),
        );

        self::assertSame(2, $loadedRule->bands->count());
        self::assertContainsOnlyInstancesOf(
            DriverPriceListConditionalRuleRewardComponent::class,
            $loadedRule->rewardComponents,
        );
        self::assertSame(
            [
                DriverPriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
                DriverPriceListConditionalRule::SOURCE_DELIVERED_PARCELS,
            ],
            $loadedRule->rewardComponents
                ->pluck('metric_source')
                ->all(),
        );

        $firstBand = $loadedRule->bands->first();

        self::assertInstanceOf(
            DriverPriceListConditionalBand::class,
            $firstBand,
        );

        self::assertSame(
            '1.5000',
            $firstBand->getAttribute('adjustment_value'),
        );

        $this->assertDatabaseCount(
            'driver_price_list_conditional_rules',
            1,
        );
        $this->assertDatabaseCount(
            'driver_price_list_conditional_rule_metric_components',
            2,
        );
        $this->assertDatabaseCount(
            'driver_price_list_conditional_rule_reward_components',
            2,
        );
        $this->assertDatabaseCount(
            'driver_price_list_conditional_bands',
            2,
        );

        $version->delete();

        $this->assertDatabaseCount(
            'driver_price_list_conditional_rules',
            0,
        );
        $this->assertDatabaseCount(
            'driver_price_list_conditional_rule_metric_components',
            0,
        );
        $this->assertDatabaseCount(
            'driver_price_list_conditional_rule_reward_components',
            0,
        );
        $this->assertDatabaseCount(
            'driver_price_list_conditional_bands',
            0,
        );
    }

    public function test_migration_backfills_legacy_reward_quantity_source(): void
    {
        Schema::drop(
            'driver_price_list_conditional_rule_reward_components',
        );

        $version = $this->createDriverPriceListVersion();

        $rule = $version->conditionalRules()->create([
            'code' => 'legacy_reward',
            'name' => 'Legacy reward',
            'description' => null,
            'metric_type' => DriverPriceListConditionalRule::METRIC_TYPE_RATIO_PERCENTAGE,
            'metric_numerator_source' => DriverPriceListConditionalRule::SOURCE_DELIVERED_PARCELS,
            'metric_denominator_source' => DriverPriceListConditionalRule::SOURCE_LOADED_PARCELS,
            'evaluation_scope' => DriverPriceListConditionalRule::EVALUATION_SCOPE_PER_ROUTE,
            'reward_method' => DriverPriceListConditionalRule::REWARD_METHOD_AMOUNT_PER_UNIT,
            'reward_quantity_source' => DriverPriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
            'reward_target_item_code' => null,
            'rounding_scale' => 2,
            'rounding_method' => DriverPriceListConditionalRule::ROUNDING_METHOD_HALF_UP,
            'position' => 1,
        ]);

        $migration = require database_path(
            'migrations/2026_08_28_190000_create_driver_price_list_conditional_rule_reward_components.php',
        );

        $migration->up();

        $this->assertDatabaseHas(
            'driver_price_list_conditional_rule_reward_components',
            [
                'driver_price_list_conditional_rule_id' => $rule->getKey(),
                'metric_source' => DriverPriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
                'position' => 1,
            ],
        );
    }

    private function createDriverPriceListVersion(): DriverPriceListVersion
    {
        $organization = Organization::query()->create([
            'name' => 'S025 master carrier',
            'type' => Organization::TYPE_MASTER,
            'status' => Organization::STATUS_ACTIVE,
        ]);

        $actor = User::factory()->create();
        $driverUser = User::factory()->create();

        $driver = Driver::query()->create([
            'user_id' => $driverUser->getKey(),
            'first_name' => 'Conditional',
            'last_name' => 'Driver',
            'phone' => null,
            'email' => null,
            'license_number' => 'S025-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);

        $assignment = DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(),
            'organization_id' => $organization->getKey(),
            'employment_type' => DriverOrganizationAssignment::EMPLOYMENT_EMPLOYEE,
            'valid_from' => '2026-08-17',
            'valid_until' => null,
            'end_reason' => null,
            'created_by_user_id' => $actor->getKey(),
            'ended_by_user_id' => null,
        ]);

        $priceList = DriverPriceList::query()->create([
            'driver_organization_assignment_id' => $assignment->getKey(),
            'managed_by_organization_id' => $organization->getKey(),
            'code' => 'S025-DPL-'.Str::random(16),
            'name' => 'Conditional driver compensation',
            'description' => null,
            'currency' => 'CZK',
            'status' => DriverPriceList::STATUS_DRAFT,
            'current_version' => 1,
            'created_by_user_id' => $actor->getKey(),
        ]);

        return $priceList->versions()->create([
            'version_number' => 1,
            'lock_version' => 1,
            'status' => DriverPriceListVersion::STATUS_DRAFT,
            'valid_from' => '2026-08-17',
            'valid_until' => null,
            'change_reason' => 'S025 persistence contract',
            'created_by_user_id' => $actor->getKey(),
            'approved_by_user_id' => null,
            'approved_at' => null,
            'activated_at' => null,
        ]);
    }
}
