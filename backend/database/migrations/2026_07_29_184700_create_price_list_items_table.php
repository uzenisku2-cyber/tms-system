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
        Schema::create('price_list_items', static function (Blueprint $table): void {
            $table->id();

            $table->foreignId('price_list_version_id')
                ->constrained('price_list_versions')
                ->restrictOnDelete();

            $table->string('code', 32);
            $table->string('description', 255)->nullable();

            $table->string(
                'calculation_method',
                32,
            )->default('quantity_times_rate');

            $table->string('unit', 16);
            $table->decimal('unit_rate', 14, 4);
            $table->char('currency', 3);
            $table->string('quantity_source', 32);
            $table->unsignedTinyInteger('rounding_scale')->default(2);
            $table->string('rounding_method', 32)->default('half_up');
            $table->unsignedSmallInteger('position');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                [
                    'price_list_version_id',
                    'code',
                ],
                'price_list_items_version_code_unique',
            );

            $table->unique(
                [
                    'price_list_version_id',
                    'position',
                ],
                'price_list_items_version_position_unique',
            );
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
ALTER TABLE price_list_items
ADD CONSTRAINT price_list_items_code_check
CHECK (
    code IN (
        'delivered_parcels',
        'redirected_parcels',
        'undelivered_parcels',
        'actual_km'
    )
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE price_list_items
ADD CONSTRAINT price_list_items_method_check
CHECK (calculation_method = 'quantity_times_rate')
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE price_list_items
ADD CONSTRAINT price_list_items_unit_check
CHECK (unit IN ('parcel', 'km'))
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE price_list_items
ADD CONSTRAINT price_list_items_quantity_source_check
CHECK (
    quantity_source IN (
        'delivered_parcels',
        'redirected_parcels',
        'undelivered_parcels',
        'actual_km'
    )
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE price_list_items
ADD CONSTRAINT price_list_items_code_mapping_check
CHECK (
    (
        code = 'actual_km'
        AND quantity_source = 'actual_km'
        AND unit = 'km'
    )
    OR
    (
        code IN (
            'delivered_parcels',
            'redirected_parcels',
            'undelivered_parcels'
        )
        AND quantity_source = code
        AND unit = 'parcel'
    )
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE price_list_items
ADD CONSTRAINT price_list_items_rate_check
CHECK (unit_rate >= 0)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE price_list_items
ADD CONSTRAINT price_list_items_currency_check
CHECK (currency ~ '^[A-Z]{3}$')
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE price_list_items
ADD CONSTRAINT price_list_items_rounding_scale_check
CHECK (rounding_scale BETWEEN 0 AND 6)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE price_list_items
ADD CONSTRAINT price_list_items_rounding_method_check
CHECK (rounding_method = 'half_up')
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE price_list_items
ADD CONSTRAINT price_list_items_position_check
CHECK (position >= 1)
SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
    }
};
