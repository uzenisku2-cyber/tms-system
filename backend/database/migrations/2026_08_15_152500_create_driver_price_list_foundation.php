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
                    'Unsupported database driver [%s] for driver price-list foundation migration.',
                    $driver,
                ),
            );
        }

        Schema::create(
            'driver_price_lists',
            static function (Blueprint $table): void {
                $table->id();
                $table->uuid('public_id')->unique();

                $table->foreignId('driver_organization_assignment_id')
                    ->constrained('driver_organization_assignments')
                    ->restrictOnDelete();

                $table->foreignId('managed_by_organization_id')
                    ->constrained('organizations')
                    ->restrictOnDelete();

                $table->string('code', 32)->unique();
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->char('currency', 3);
                $table->string('status', 32)->default('draft');
                $table->unsignedInteger('current_version')->default(1);

                $table->foreignId('created_by_user_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->timestamps();

                $table->index(
                    [
                        'driver_organization_assignment_id',
                        'status',
                    ],
                    'driver_price_lists_assignment_status_index',
                );

                $table->index(
                    [
                        'managed_by_organization_id',
                        'status',
                    ],
                    'driver_price_lists_manager_status_index',
                );
            },
        );

        Schema::create(
            'driver_price_list_versions',
            static function (Blueprint $table): void {
                $table->id();

                $table->foreignId('driver_price_list_id')
                    ->constrained('driver_price_lists')
                    ->restrictOnDelete();

                $table->unsignedInteger('version_number');
                $table->unsignedInteger('lock_version')->default(1);
                $table->string('status', 32)->default('draft');
                $table->date('valid_from')->nullable();
                $table->date('valid_until')->nullable();
                $table->text('change_reason')->nullable();

                $table->foreignId('created_by_user_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->foreignId('approved_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->timestamp('approved_at')->nullable();
                $table->timestamp('activated_at')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(
                    [
                        'driver_price_list_id',
                        'version_number',
                    ],
                    'driver_price_list_versions_list_version_unique',
                );

                $table->index(
                    [
                        'driver_price_list_id',
                        'status',
                    ],
                    'driver_price_list_versions_list_status_index',
                );

                $table->index(
                    [
                        'driver_price_list_id',
                        'valid_from',
                        'valid_until',
                    ],
                    'driver_price_list_versions_list_validity_index',
                );
            },
        );

        Schema::create(
            'driver_price_list_items',
            static function (Blueprint $table): void {
                $table->id();

                $table->foreignId('driver_price_list_version_id')
                    ->constrained('driver_price_list_versions')
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
                        'driver_price_list_version_id',
                        'code',
                    ],
                    'driver_price_list_items_version_code_unique',
                );

                $table->unique(
                    [
                        'driver_price_list_version_id',
                        'position',
                    ],
                    'driver_price_list_items_version_position_unique',
                );
            },
        );

        if ($driver !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_lists
ADD CONSTRAINT driver_price_lists_status_check
CHECK (status IN ('draft', 'active', 'archived'))
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_lists
ADD CONSTRAINT driver_price_lists_current_version_check
CHECK (current_version >= 1)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_lists
ADD CONSTRAINT driver_price_lists_currency_check
CHECK (currency ~ '^[A-Z]{3}$')
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_lists
ADD CONSTRAINT driver_price_lists_code_check
CHECK (code <> '' AND code = btrim(code))
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_lists
ADD CONSTRAINT driver_price_lists_name_check
CHECK (name <> '' AND name = btrim(name))
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_versions
ADD CONSTRAINT driver_price_list_versions_number_check
CHECK (version_number >= 1)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_versions
ADD CONSTRAINT driver_price_list_versions_lock_version_check
CHECK (lock_version >= 1)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_versions
ADD CONSTRAINT driver_price_list_versions_status_check
CHECK (
    status IN (
        'draft',
        'approved',
        'active',
        'replaced',
        'expired'
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_versions
ADD CONSTRAINT driver_price_list_versions_validity_check
CHECK (
    valid_until IS NULL
    OR valid_from IS NULL
    OR valid_until >= valid_from
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_versions
ADD CONSTRAINT driver_price_list_versions_approval_check
CHECK (
    status = 'draft'
    OR (
        approved_by_user_id IS NOT NULL
        AND approved_at IS NOT NULL
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_versions
ADD CONSTRAINT driver_price_list_versions_activation_check
CHECK (
    status NOT IN ('active', 'replaced', 'expired')
    OR (
        valid_from IS NOT NULL
        AND activated_at IS NOT NULL
    )
)
SQL);

        DB::statement(<<<'SQL'
CREATE EXTENSION IF NOT EXISTS btree_gist
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_versions
ADD CONSTRAINT driver_price_list_versions_period_exclusion
EXCLUDE USING gist (
    driver_price_list_id WITH =,
    daterange(
        valid_from,
        valid_until,
        '[]'
    ) WITH &&
)
WHERE (
    status IN ('active', 'replaced', 'expired')
    AND valid_from IS NOT NULL
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_items
ADD CONSTRAINT driver_price_list_items_code_check
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
ALTER TABLE driver_price_list_items
ADD CONSTRAINT driver_price_list_items_method_check
CHECK (calculation_method = 'quantity_times_rate')
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_items
ADD CONSTRAINT driver_price_list_items_unit_check
CHECK (unit IN ('parcel', 'km'))
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_items
ADD CONSTRAINT driver_price_list_items_quantity_source_check
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
ALTER TABLE driver_price_list_items
ADD CONSTRAINT driver_price_list_items_mapping_check
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
ALTER TABLE driver_price_list_items
ADD CONSTRAINT driver_price_list_items_rate_check
CHECK (unit_rate >= 0)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_items
ADD CONSTRAINT driver_price_list_items_currency_check
CHECK (currency ~ '^[A-Z]{3}$')
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_items
ADD CONSTRAINT driver_price_list_items_rounding_scale_check
CHECK (rounding_scale BETWEEN 0 AND 6)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_items
ADD CONSTRAINT driver_price_list_items_rounding_method_check
CHECK (rounding_method = 'half_up')
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_price_list_items
ADD CONSTRAINT driver_price_list_items_position_check
CHECK (position >= 1)
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_price_list_items');
        Schema::dropIfExists('driver_price_list_versions');
        Schema::dropIfExists('driver_price_lists');
    }
};
