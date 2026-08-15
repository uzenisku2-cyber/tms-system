<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'financial_conditional_adjustments',
            static function (Blueprint $table): void {
                $table->id();

                $table->uuid('public_id')
                    ->unique();

                $table->foreignId('organization_id')
                    ->constrained('organizations')
                    ->restrictOnDelete();

                $table->foreignId(
                    'organization_relationship_id',
                )
                    ->constrained(
                        'organization_relationships',
                    )
                    ->restrictOnDelete();

                $table->foreignId('price_list_id')
                    ->constrained('price_lists')
                    ->restrictOnDelete();

                $table->foreignId(
                    'price_list_version_id',
                )
                    ->constrained('price_list_versions')
                    ->restrictOnDelete();

                $table->foreignId(
                    'price_list_conditional_rule_id',
                )
                    ->constrained(
                        'price_list_conditional_rules',
                    )
                    ->restrictOnDelete();

                $table->foreignId(
                    'price_list_conditional_band_id',
                )
                    ->nullable()
                    ->constrained(
                        'price_list_conditional_bands',
                    )
                    ->restrictOnDelete();

                $table->foreignId(
                    'performed_by_driver_id',
                )
                    ->constrained('drivers')
                    ->restrictOnDelete();

                $table->string(
                    'evaluation_scope',
                    32,
                );

                $table->date('period_start');
                $table->date('period_end');

                $table->unsignedInteger(
                    'calculation_version',
                )->default(1);

                $table->char('currency', 3);

                $table->string(
                    'metric_type',
                    32,
                );

                $table->string(
                    'metric_numerator_source',
                    64,
                );

                $table->decimal(
                    'metric_numerator_value',
                    18,
                    6,
                );

                $table->string(
                    'metric_denominator_source',
                    64,
                )->nullable();

                $table->decimal(
                    'metric_denominator_value',
                    18,
                    6,
                )->nullable();

                $table->decimal(
                    'metric_value',
                    18,
                    6,
                );

                $table->string(
                    'reward_method',
                    32,
                );

                $table->string(
                    'reward_quantity_source',
                    64,
                )->nullable();

                $table->decimal(
                    'reward_quantity_value',
                    18,
                    6,
                )->nullable();

                $table->string(
                    'reward_target_item_code',
                    64,
                )->nullable();

                $table->decimal(
                    'reward_target_item_amount',
                    16,
                    2,
                )->nullable();

                $table->decimal(
                    'adjustment_value',
                    16,
                    4,
                )->nullable();

                $table->decimal(
                    'conditional_amount',
                    16,
                    2,
                );

                $table->jsonb(
                    'evaluation_snapshot',
                );

                $table->foreignId(
                    'calculated_by_user_id',
                )
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->timestamp(
                    'calculated_at',
                );

                $table->unsignedBigInteger(
                    'supersedes_adjustment_id',
                )->nullable();

                $table->timestamp(
                    'created_at',
                )->useCurrent();

                $table->unique(
                    [
                        'price_list_conditional_rule_id',
                        'performed_by_driver_id',
                        'period_start',
                        'period_end',
                        'calculation_version',
                    ],
                    'fin_cond_adj_scope_version_unique',
                );

                $table->unique(
                    'supersedes_adjustment_id',
                    'fin_cond_adj_supersedes_unique',
                );

                $table->index(
                    [
                        'organization_relationship_id',
                        'period_start',
                        'period_end',
                    ],
                    'fin_cond_adj_relationship_period_index',
                );

                $table->index(
                    [
                        'performed_by_driver_id',
                        'period_start',
                        'period_end',
                    ],
                    'fin_cond_adj_driver_period_index',
                );

                $table->index(
                    [
                        'price_list_id',
                        'price_list_version_id',
                    ],
                    'fin_cond_adj_price_version_index',
                );
            },
        );

        Schema::table(
            'financial_conditional_adjustments',
            static function (Blueprint $table): void {
                $table->foreign(
                    'supersedes_adjustment_id',
                    'fin_cond_adj_supersedes_foreign',
                )
                    ->references('id')
                    ->on(
                        'financial_conditional_adjustments',
                    )
                    ->restrictOnDelete();
            },
        );

        Schema::create(
            'financial_conditional_adjustment_sources',
            static function (Blueprint $table): void {
                $table->id();

                $table->foreignId(
                    'financial_conditional_adjustment_id',
                )
                    ->constrained(
                        'financial_conditional_adjustments',
                    )
                    ->restrictOnDelete();

                $table->foreignId(
                    'financial_calculation_id',
                )
                    ->constrained(
                        'financial_calculations',
                    )
                    ->restrictOnDelete();

                $table->unsignedSmallInteger(
                    'source_position',
                );

                $table->timestamp(
                    'created_at',
                )->useCurrent();

                $table->unique(
                    [
                        'financial_conditional_adjustment_id',
                        'financial_calculation_id',
                    ],
                    'fin_cond_adj_source_member_unique',
                );

                $table->unique(
                    [
                        'financial_conditional_adjustment_id',
                        'source_position',
                    ],
                    'fin_cond_adj_source_position_unique',
                );

                $table->index(
                    'financial_calculation_id',
                    'fin_cond_adj_source_calculation_index',
                );
            },
        );

        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
ALTER TABLE financial_conditional_adjustments
ADD CONSTRAINT fin_cond_adj_scope_check
CHECK (
    evaluation_scope IN ('per_route', 'monthly_driver')
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE financial_conditional_adjustments
ADD CONSTRAINT fin_cond_adj_period_check
CHECK (
    period_start <= period_end
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE financial_conditional_adjustments
ADD CONSTRAINT fin_cond_adj_version_check
CHECK (
    calculation_version >= 1
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE financial_conditional_adjustments
ADD CONSTRAINT fin_cond_adj_currency_check
CHECK (
    currency ~ '^[A-Z]{3}$'
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE financial_conditional_adjustments
ADD CONSTRAINT fin_cond_adj_metric_type_check
CHECK (
    metric_type IN ('ratio_percentage', 'quantity')
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE financial_conditional_adjustments
ADD CONSTRAINT fin_cond_adj_metric_source_check
CHECK (
    metric_numerator_source IN (
        'loaded_parcels',
        'delivered_parcels',
        'redirected_parcels',
        'customer_rejected_parcels',
        'not_delivered_parcels',
        'processed_parcels',
        'actual_km',
        'planned_km'
    )
    AND (
        metric_denominator_source IS NULL
        OR metric_denominator_source IN (
            'loaded_parcels',
            'delivered_parcels',
            'redirected_parcels',
            'customer_rejected_parcels',
            'not_delivered_parcels',
            'processed_parcels',
            'actual_km',
            'planned_km'
        )
    )
    AND (
        reward_quantity_source IS NULL
        OR reward_quantity_source IN (
            'loaded_parcels',
            'delivered_parcels',
            'redirected_parcels',
            'customer_rejected_parcels',
            'not_delivered_parcels',
            'processed_parcels',
            'actual_km',
            'planned_km'
        )
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE financial_conditional_adjustments
ADD CONSTRAINT fin_cond_adj_denominator_check
CHECK (
    (
        metric_type = 'ratio_percentage'
        AND metric_denominator_source IS NOT NULL
        AND metric_denominator_value IS NOT NULL
    )
    OR
    (
        metric_type = 'quantity'
        AND metric_denominator_source IS NULL
        AND metric_denominator_value IS NULL
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE financial_conditional_adjustments
ADD CONSTRAINT fin_cond_adj_reward_method_check
CHECK (
    reward_method IN (
        'amount_per_unit',
        'fixed_amount',
        'percentage_of_item'
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE financial_conditional_adjustments
ADD CONSTRAINT fin_cond_adj_reward_target_check
CHECK (
    reward_target_item_code IS NULL
    OR reward_target_item_code IN (
        'delivered_parcels',
        'redirected_parcels',
        'undelivered_parcels',
        'actual_km'
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE financial_conditional_adjustments
ADD CONSTRAINT fin_cond_adj_reward_shape_check
CHECK (
    (
        reward_method = 'amount_per_unit'
        AND reward_quantity_source IS NOT NULL
        AND reward_quantity_value IS NOT NULL
        AND reward_target_item_code IS NULL
        AND reward_target_item_amount IS NULL
    )
    OR
    (
        reward_method = 'fixed_amount'
        AND reward_quantity_source IS NULL
        AND reward_quantity_value IS NULL
        AND reward_target_item_code IS NULL
        AND reward_target_item_amount IS NULL
    )
    OR
    (
        reward_method = 'percentage_of_item'
        AND reward_quantity_source IS NULL
        AND reward_quantity_value IS NULL
        AND reward_target_item_code IS NOT NULL
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE financial_conditional_adjustments
ADD CONSTRAINT fin_cond_adj_band_result_check
CHECK (
    (
        price_list_conditional_band_id IS NULL
        AND adjustment_value IS NULL
        AND conditional_amount = 0
    )
    OR
    (
        price_list_conditional_band_id IS NOT NULL
        AND adjustment_value IS NOT NULL
        AND (
            reward_method <> 'percentage_of_item'
            OR reward_target_item_amount IS NOT NULL
        )
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE financial_conditional_adjustments
ADD CONSTRAINT fin_cond_adj_values_check
CHECK (
    metric_numerator_value >= 0
    AND (
        metric_denominator_value IS NULL
        OR metric_denominator_value >= 0
    )
    AND metric_value >= 0
    AND (
        reward_quantity_value IS NULL
        OR reward_quantity_value >= 0
    )
    AND (
        reward_target_item_amount IS NULL
        OR reward_target_item_amount >= 0
    )
    AND (
        adjustment_value IS NULL
        OR adjustment_value >= 0
    )
    AND conditional_amount >= 0
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE financial_conditional_adjustments
ADD CONSTRAINT fin_cond_adj_supersedes_self_check
CHECK (
    supersedes_adjustment_id IS NULL
    OR supersedes_adjustment_id <> id
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE financial_conditional_adjustment_sources
ADD CONSTRAINT fin_cond_adj_sources_position_check
CHECK (
    source_position >= 1
)
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'financial_conditional_adjustment_sources',
        );

        Schema::dropIfExists(
            'financial_conditional_adjustments',
        );
    }
};
