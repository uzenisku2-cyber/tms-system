<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class FinancialConditionalAdjustmentFoundationContractTest extends TestCase
{
    private function migrationSource(): string
    {
        $path =
            dirname(__DIR__, 4)
            .'/database/migrations/'
            .'2026_08_13_165500_'
            .'create_financial_conditional_adjustment_foundation.php';

        $source = file_get_contents($path);

        self::assertIsString($source);

        return $source;
    }

    public function test_scope_level_adjustment_is_separate_from_route_lines(): void
    {
        $source = $this->migrationSource();

        self::assertStringContainsString(
            'financial_conditional_adjustments',
            $source,
        );

        self::assertStringContainsString(
            'financial_conditional_adjustment_sources',
            $source,
        );

        self::assertStringNotContainsString(
            "Schema::table('financial_calculation_lines'",
            $source,
        );

        self::assertStringContainsString(
            'financial_calculation_id',
            $source,
        );
    }

    public function test_monthly_and_per_route_audit_fields_are_explicit(): void
    {
        $source = $this->migrationSource();

        foreach ([
            'evaluation_scope',
            'period_start',
            'period_end',
            'performed_by_driver_id',
            'metric_numerator_source',
            'metric_numerator_value',
            'metric_denominator_source',
            'metric_denominator_value',
            'metric_value',
            'reward_method',
            'reward_quantity_source',
            'reward_quantity_value',
            'reward_target_item_code',
            'reward_target_item_amount',
            'adjustment_value',
            'conditional_amount',
            'evaluation_snapshot',
            'calculation_version',
            'supersedes_adjustment_id',
        ] as $field) {
            self::assertStringContainsString(
                $field,
                $source,
            );
        }

        self::assertStringContainsString(
            "'per_route', 'monthly_driver'",
            $source,
        );
    }

    public function test_business_shapes_and_source_membership_are_guarded(): void
    {
        $source = $this->migrationSource();

        foreach ([
            'fin_cond_adj_denominator_check',
            'fin_cond_adj_reward_shape_check',
            'fin_cond_adj_band_result_check',
            'fin_cond_adj_values_check',
            'fin_cond_adj_scope_version_unique',
            'fin_cond_adj_supersedes_unique',
            'fin_cond_adj_source_member_unique',
            'fin_cond_adj_source_position_unique',
            'fin_cond_adj_sources_position_check',
        ] as $contract) {
            self::assertStringContainsString(
                $contract,
                $source,
            );
        }

        self::assertStringContainsString(
            "'customer_rejected_parcels'",
            $source,
        );

        self::assertStringContainsString(
            "'not_delivered_parcels'",
            $source,
        );

        self::assertStringContainsString(
            "'processed_parcels'",
            $source,
        );
    }
}
