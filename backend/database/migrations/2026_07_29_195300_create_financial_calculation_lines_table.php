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
        Schema::create('financial_calculation_lines', static function (Blueprint $table): void {
            $table->id();

            $table->foreignId('financial_calculation_id')
                ->constrained('financial_calculations')
                ->restrictOnDelete();

            $table->foreignId('price_list_item_id')
                ->constrained('price_list_items')
                ->restrictOnDelete();

            $table->string('pricing_code', 32);
            $table->string('description', 255)->nullable();
            $table->decimal('quantity', 14, 3);
            $table->string('unit', 16);
            $table->decimal('unit_rate', 14, 4);
            $table->char('currency', 3);
            $table->decimal('line_amount', 16, 2);
            $table->string('source_field', 32);
            $table->unsignedTinyInteger('rounding_scale')->default(2);
            $table->string('rounding_method', 32)->default('half_up');
            $table->unsignedSmallInteger('position');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                [
                    'financial_calculation_id',
                    'pricing_code',
                ],
                'fin_calc_lines_calc_code_unique',
            );

            $table->unique(
                [
                    'financial_calculation_id',
                    'position',
                ],
                'fin_calc_lines_calc_position_unique',
            );

            $table->index(
                [
                    'financial_calculation_id',
                    'price_list_item_id',
                ],
                'fin_calc_lines_calc_item_index',
            );
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
ALTER TABLE financial_calculation_lines
ADD CONSTRAINT fin_calc_lines_code_check
CHECK (
    pricing_code IN (
        'delivered_parcels',
        'redirected_parcels',
        'undelivered_parcels',
        'actual_km'
    )
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE financial_calculation_lines
ADD CONSTRAINT fin_calc_lines_source_check
CHECK (
    source_field IN (
        'delivered_parcels',
        'redirected_parcels',
        'undelivered_parcels',
        'actual_km'
    )
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE financial_calculation_lines
ADD CONSTRAINT fin_calc_lines_unit_check
CHECK (unit IN ('parcel', 'km'))
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE financial_calculation_lines
ADD CONSTRAINT fin_calc_lines_mapping_check
CHECK (
    (
        pricing_code = 'actual_km'
        AND source_field = 'actual_km'
        AND unit = 'km'
    )
    OR
    (
        pricing_code IN (
            'delivered_parcels',
            'redirected_parcels',
            'undelivered_parcels'
        )
        AND source_field = pricing_code
        AND unit = 'parcel'
    )
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE financial_calculation_lines
ADD CONSTRAINT fin_calc_lines_values_check
CHECK (
    quantity >= 0
    AND unit_rate >= 0
    AND line_amount >= 0
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE financial_calculation_lines
ADD CONSTRAINT fin_calc_lines_currency_check
CHECK (currency ~ '^[A-Z]{3}$')
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE financial_calculation_lines
ADD CONSTRAINT fin_calc_lines_rounding_scale_check
CHECK (rounding_scale BETWEEN 0 AND 6)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE financial_calculation_lines
ADD CONSTRAINT fin_calc_lines_rounding_method_check
CHECK (rounding_method = 'half_up')
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE financial_calculation_lines
ADD CONSTRAINT fin_calc_lines_position_check
CHECK (position >= 1)
SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_calculation_lines');
    }
};
