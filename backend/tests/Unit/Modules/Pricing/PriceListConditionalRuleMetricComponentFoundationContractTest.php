<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Modules\Pricing\Models\PriceListConditionalRule;
use App\Modules\Pricing\Models\PriceListConditionalRuleMetricComponent;
use PHPUnit\Framework\TestCase;

final class PriceListConditionalRuleMetricComponentFoundationContractTest extends TestCase
{
    public function test_metric_formula_supports_unlimited_selected_sources(): void
    {
        $migration = file_get_contents(
            dirname(__DIR__, 4)
                .'/database/migrations/'
                .'2026_08_16_190000_create_price_list_conditional_rule_metric_components.php',
        );

        self::assertIsString($migration);

        self::assertStringContainsString(
            'price_list_conditional_rule_metric_components',
            $migration,
        );

        self::assertStringContainsString(
            "'component_role'",
            $migration,
        );

        self::assertStringContainsString(
            "'metric_source'",
            $migration,
        );

        self::assertStringNotContainsString(
            'maximum_component_count',
            $migration,
        );
    }

    public function test_formula_components_are_explicitly_classified(): void
    {
        self::assertSame(
            [
                'numerator',
                'denominator',
            ],
            PriceListConditionalRuleMetricComponent::ROLES,
        );

        foreach ([
            'loaded_parcels',
            'delivered_parcels',
            'redirected_parcels',
            'customer_rejected_parcels',
            'not_delivered_parcels',
            'processed_parcels',
        ] as $source) {
            self::assertContains(
                $source,
                PriceListConditionalRule::METRIC_SOURCES,
            );
        }
    }

    public function test_monthly_price_list_scope_is_independent_of_driver(): void
    {
        self::assertContains(
            'monthly_price_list',
            PriceListConditionalRule::EVALUATION_SCOPES,
        );

        self::assertSame(
            'monthly_price_list',
            PriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_PRICE_LIST,
        );
    }

    public function test_legacy_single_source_contract_remains_available(): void
    {
        $model = file_get_contents(
            dirname(__DIR__, 4)
                .'/app/Modules/Pricing/Models/'
                .'PriceListConditionalRule.php',
        );

        self::assertIsString($model);

        self::assertStringContainsString(
            "'metric_numerator_source'",
            $model,
        );

        self::assertStringContainsString(
            "'metric_denominator_source'",
            $model,
        );

        self::assertStringContainsString(
            'metricComponents()',
            $model,
        );
    }
}
