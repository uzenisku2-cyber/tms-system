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
        Schema::create(
            'price_list_conditional_rule_metric_components',
            static function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'price_list_conditional_rule_id',
                );

                $table->foreign(
                    'price_list_conditional_rule_id',
                    'pl_cond_components_rule_fk',
                )
                    ->references('id')
                    ->on('price_list_conditional_rules')
                    ->restrictOnDelete();

                $table->string('component_role', 32);
                $table->string('metric_source', 64);
                $table->unsignedSmallInteger('position');
                $table->timestamp('created_at')->useCurrent();

                $table->unique(
                    [
                        'price_list_conditional_rule_id',
                        'component_role',
                        'metric_source',
                    ],
                    'pl_cond_components_source_unique',
                );

                $table->unique(
                    [
                        'price_list_conditional_rule_id',
                        'component_role',
                        'position',
                    ],
                    'pl_cond_components_position_unique',
                );
            },
        );

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
ALTER TABLE price_list_conditional_rule_metric_components
ADD CONSTRAINT pl_cond_components_role_check
CHECK (
    component_role IN ('numerator', 'denominator')
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE price_list_conditional_rule_metric_components
ADD CONSTRAINT pl_cond_components_source_check
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
ALTER TABLE price_list_conditional_rule_metric_components
ADD CONSTRAINT pl_cond_components_position_check
CHECK (
    position >= 1
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE price_list_conditional_rules
DROP CONSTRAINT pl_cond_rules_scope_check
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE price_list_conditional_rules
ADD CONSTRAINT pl_cond_rules_scope_check
CHECK (
    evaluation_scope IN (
        'per_route',
        'monthly_driver',
        'monthly_price_list'
    )
)
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
ALTER TABLE price_list_conditional_rules
DROP CONSTRAINT IF EXISTS pl_cond_rules_scope_check
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE price_list_conditional_rules
ADD CONSTRAINT pl_cond_rules_scope_check
CHECK (
    evaluation_scope IN (
        'per_route',
        'monthly_driver'
    )
)
SQL);
        }

        Schema::dropIfExists(
            'price_list_conditional_rule_metric_components',
        );
    }
};
