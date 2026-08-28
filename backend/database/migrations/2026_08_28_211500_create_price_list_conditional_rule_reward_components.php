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
                    'Unsupported database driver [%s] for conditional reward components migration.',
                    $driver,
                ),
            );
        }

        Schema::create(
            'price_list_conditional_rule_reward_components',
            static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger(
                    'price_list_conditional_rule_id',
                );
                $table->string('metric_source', 64);
                $table->unsignedSmallInteger('position');
                $table->timestamp('created_at')->useCurrent();

                $table->foreign(
                    'price_list_conditional_rule_id',
                    'pl_cond_reward_components_rule_fk',
                )
                    ->references('id')
                    ->on('price_list_conditional_rules')
                    ->cascadeOnDelete();

                $table->unique(
                    [
                        'price_list_conditional_rule_id',
                        'metric_source',
                    ],
                    'pl_cond_reward_components_source_unique',
                );

                $table->unique(
                    [
                        'price_list_conditional_rule_id',
                        'position',
                    ],
                    'pl_cond_reward_components_position_unique',
                );
            },
        );

        if ($driver === 'pgsql') {
            DB::statement(<<<'SQL'
ALTER TABLE price_list_conditional_rule_reward_components
ADD CONSTRAINT pl_cond_reward_components_source_check
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
ALTER TABLE price_list_conditional_rule_reward_components
ADD CONSTRAINT pl_cond_reward_components_position_check
CHECK (position >= 1)
SQL);
        }

        DB::statement(<<<'SQL'
INSERT INTO price_list_conditional_rule_reward_components (
    price_list_conditional_rule_id,
    metric_source,
    position,
    created_at
)
SELECT
    id,
    reward_quantity_source,
    1,
    CURRENT_TIMESTAMP
FROM price_list_conditional_rules
WHERE reward_method = 'amount_per_unit'
  AND reward_quantity_source IS NOT NULL
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'price_list_conditional_rule_reward_components',
        );
    }
};
