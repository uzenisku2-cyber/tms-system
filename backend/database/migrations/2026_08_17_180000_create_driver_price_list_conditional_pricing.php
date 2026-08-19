<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = true;

    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if (! in_array($driver, ['pgsql', 'sqlite'], true)) {
            throw new RuntimeException(
                sprintf(
                    'Unsupported database driver [%s] for driver conditional pricing migration.',
                    $driver,
                ),
            );
        }

        Schema::create(
            'driver_price_list_conditional_rules',
            static function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'driver_price_list_version_id',
                );

                $table->string('code', 64);
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->string('metric_type', 32);

                $table->string(
                    'metric_numerator_source',
                    64,
                );

                $table->string(
                    'metric_denominator_source',
                    64,
                )->nullable();

                $table->string('evaluation_scope', 32);
                $table->string('reward_method', 32);

                $table->string(
                    'reward_quantity_source',
                    64,
                )->nullable();

                $table->string(
                    'reward_target_item_code',
                    32,
                )->nullable();

                $table
                    ->unsignedTinyInteger('rounding_scale')
                    ->default(2);

                $table
                    ->string('rounding_method', 32)
                    ->default('half_up');

                $table->unsignedSmallInteger('position');
                $table->timestamp('created_at')->useCurrent();

                $table->foreign(
                    'driver_price_list_version_id',
                    'dpl_cond_rules_version_fk',
                )
                    ->references('id')
                    ->on('driver_price_list_versions')
                    ->cascadeOnDelete();

                $table->unique(
                    [
                        'driver_price_list_version_id',
                        'code',
                    ],
                    'dpl_cond_rules_version_code_unique',
                );

                $table->unique(
                    [
                        'driver_price_list_version_id',
                        'position',
                    ],
                    'dpl_cond_rules_version_position_unique',
                );
            },
        );

        Schema::create(
            'driver_price_list_conditional_rule_metric_components',
            static function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'driver_price_list_conditional_rule_id',
                );

                $table->string('component_role', 32);
                $table->string('metric_source', 64);
                $table->unsignedSmallInteger('position');
                $table->timestamp('created_at')->useCurrent();

                $table->foreign(
                    'driver_price_list_conditional_rule_id',
                    'dpl_cond_components_rule_fk',
                )
                    ->references('id')
                    ->on('driver_price_list_conditional_rules')
                    ->cascadeOnDelete();

                $table->unique(
                    [
                        'driver_price_list_conditional_rule_id',
                        'component_role',
                        'metric_source',
                    ],
                    'dpl_cond_components_source_unique',
                );

                $table->unique(
                    [
                        'driver_price_list_conditional_rule_id',
                        'component_role',
                        'position',
                    ],
                    'dpl_cond_components_position_unique',
                );
            },
        );

        Schema::create(
            'driver_price_list_conditional_bands',
            static function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'driver_price_list_conditional_rule_id',
                );

                $table
                    ->decimal('minimum_value', 14, 4)
                    ->nullable();

                $table
                    ->decimal('maximum_value', 14, 4)
                    ->nullable();

                $table
                    ->boolean('minimum_inclusive')
                    ->default(true);

                $table
                    ->boolean('maximum_inclusive')
                    ->default(false);

                $table->decimal(
                    'adjustment_value',
                    14,
                    4,
                );

                $table->unsignedSmallInteger('position');
                $table->timestamp('created_at')->useCurrent();

                $table->foreign(
                    'driver_price_list_conditional_rule_id',
                    'dpl_cond_bands_rule_fk',
                )
                    ->references('id')
                    ->on('driver_price_list_conditional_rules')
                    ->cascadeOnDelete();

                $table->unique(
                    [
                        'driver_price_list_conditional_rule_id',
                        'position',
                    ],
                    'dpl_cond_bands_rule_position_unique',
                );
            },
        );

        if ($driver !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_conditional_rules
ADD CONSTRAINT dpl_cond_rules_code_check
CHECK (
    code ~ '^[a-z][a-z0-9_]{0,63}$'
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_conditional_rules
ADD CONSTRAINT dpl_cond_rules_metric_type_check
CHECK (
    metric_type IN (
        'ratio_percentage',
        'quantity'
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_conditional_rules
ADD CONSTRAINT dpl_cond_rules_scope_check
CHECK (
    evaluation_scope IN (
        'per_route',
        'monthly_driver',
        'monthly_price_list'
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_conditional_rules
ADD CONSTRAINT dpl_cond_rules_source_check
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
ALTER TABLE driver_price_list_conditional_rules
ADD CONSTRAINT dpl_cond_rules_metric_shape_check
CHECK (
    (
        metric_type = 'ratio_percentage'
        AND metric_denominator_source IS NOT NULL
    )
    OR
    (
        metric_type = 'quantity'
        AND metric_denominator_source IS NULL
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_conditional_rules
ADD CONSTRAINT dpl_cond_rules_reward_method_check
CHECK (
    reward_method IN (
        'amount_per_unit',
        'fixed_amount',
        'percentage_of_item'
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_conditional_rules
ADD CONSTRAINT dpl_cond_rules_target_item_check
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
ALTER TABLE driver_price_list_conditional_rules
ADD CONSTRAINT dpl_cond_rules_reward_shape_check
CHECK (
    (
        reward_method = 'amount_per_unit'
        AND reward_quantity_source IS NOT NULL
        AND reward_target_item_code IS NULL
    )
    OR
    (
        reward_method = 'fixed_amount'
        AND reward_quantity_source IS NULL
        AND reward_target_item_code IS NULL
    )
    OR
    (
        reward_method = 'percentage_of_item'
        AND reward_quantity_source IS NULL
        AND reward_target_item_code IS NOT NULL
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_conditional_rules
ADD CONSTRAINT dpl_cond_rules_round_scale_check
CHECK (
    rounding_scale BETWEEN 0 AND 6
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_conditional_rules
ADD CONSTRAINT dpl_cond_rules_round_method_check
CHECK (
    rounding_method = 'half_up'
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_conditional_rules
ADD CONSTRAINT dpl_cond_rules_position_check
CHECK (
    position >= 1
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_conditional_rule_metric_components
ADD CONSTRAINT dpl_cond_components_role_check
CHECK (
    component_role IN ('numerator', 'denominator')
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_conditional_rule_metric_components
ADD CONSTRAINT dpl_cond_components_source_check
CHECK (
    metric_source IN (
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
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_conditional_rule_metric_components
ADD CONSTRAINT dpl_cond_components_position_check
CHECK (
    position >= 1
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_conditional_bands
ADD CONSTRAINT dpl_cond_bands_bounds_check
CHECK (
    (
        minimum_value IS NOT NULL
        OR maximum_value IS NOT NULL
    )
    AND (
        minimum_value IS NULL
        OR minimum_value >= 0
    )
    AND (
        maximum_value IS NULL
        OR maximum_value >= 0
    )
    AND (
        minimum_value IS NULL
        OR maximum_value IS NULL
        OR minimum_value < maximum_value
        OR (
            minimum_value = maximum_value
            AND minimum_inclusive
            AND maximum_inclusive
        )
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_conditional_bands
ADD CONSTRAINT dpl_cond_bands_value_check
CHECK (
    adjustment_value >= 0
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_conditional_bands
ADD CONSTRAINT dpl_cond_bands_position_check
CHECK (
    position >= 1
)
SQL);

        DB::statement(
            'CREATE EXTENSION IF NOT EXISTS btree_gist',
        );

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_conditional_bands
ADD COLUMN value_range numrange
GENERATED ALWAYS AS (
    numrange(
        minimum_value,
        maximum_value,
        CASE
            WHEN minimum_inclusive
                AND maximum_inclusive
                THEN '[]'
            WHEN minimum_inclusive
                AND NOT maximum_inclusive
                THEN '[)'
            WHEN NOT minimum_inclusive
                AND maximum_inclusive
                THEN '(]'
            ELSE '()'
        END
    )
) STORED
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_conditional_bands
ADD CONSTRAINT dpl_cond_bands_range_exclusion
EXCLUDE USING gist (
    driver_price_list_conditional_rule_id WITH =,
    value_range WITH &&
)
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'driver_price_list_conditional_bands',
        );

        Schema::dropIfExists(
            'driver_price_list_conditional_rule_metric_components',
        );

        Schema::dropIfExists(
            'driver_price_list_conditional_rules',
        );
    }
};
