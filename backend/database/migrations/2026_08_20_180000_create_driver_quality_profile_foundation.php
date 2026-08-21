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
                    'Unsupported database driver [%s] for driver quality-profile foundation migration.',
                    $driver,
                ),
            );
        }

        Schema::create(
            'driver_quality_profiles',
            static function (Blueprint $table): void {
                $table->id();
                $table->uuid('public_id')->unique();

                $table->foreignId('organization_id')
                    ->constrained('organizations')
                    ->restrictOnDelete();

                $table->string('code', 32);
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->string('status', 32)->default('active');
                $table->unsignedInteger('current_version')->default(1);

                $table->foreignId('created_by_user_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->timestamps();

                $table->unique(
                    ['organization_id', 'code'],
                    'driver_quality_profiles_org_code_unique',
                );

                $table->index(
                    ['organization_id', 'status'],
                    'driver_quality_profiles_org_status_index',
                );
            },
        );

        Schema::create(
            'driver_quality_profile_versions',
            static function (Blueprint $table): void {
                $table->id();

                $table->foreignId('driver_quality_profile_id')
                    ->constrained('driver_quality_profiles')
                    ->restrictOnDelete();

                $table->unsignedInteger('version_number');
                $table->unsignedInteger('lock_version')->default(1);
                $table->string('status', 32)->default('draft');
                $table->string(
                    'calculation_method',
                    32,
                )->default('processed_share');
                $table->date('valid_from')->nullable();
                $table->date('valid_until')->nullable();
                $table->text('change_reason')->nullable();

                $table->foreignId('created_by_user_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->foreignId('activated_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->timestamp('activated_at')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(
                    [
                        'driver_quality_profile_id',
                        'version_number',
                    ],
                    'driver_quality_profile_versions_profile_version_unique',
                );

                $table->index(
                    [
                        'driver_quality_profile_id',
                        'status',
                    ],
                    'driver_quality_profile_versions_profile_status_index',
                );

                $table->index(
                    [
                        'driver_quality_profile_id',
                        'valid_from',
                        'valid_until',
                    ],
                    'driver_quality_profile_versions_profile_validity_index',
                );
            },
        );

        Schema::create(
            'driver_quality_profile_components',
            static function (Blueprint $table): void {
                $table->id();

                $table->foreignId('driver_quality_profile_version_id')
                    ->constrained('driver_quality_profile_versions')
                    ->cascadeOnDelete();

                $table->string('source_code', 32);
                $table->unsignedTinyInteger('position');
                $table->timestamp('created_at')->useCurrent();

                $table->unique(
                    [
                        'driver_quality_profile_version_id',
                        'source_code',
                    ],
                    'driver_quality_components_version_source_unique',
                );

                $table->unique(
                    [
                        'driver_quality_profile_version_id',
                        'position',
                    ],
                    'driver_quality_components_version_position_unique',
                );
            },
        );

        Schema::create(
            'driver_quality_profile_bindings',
            static function (Blueprint $table): void {
                $table->id();

                $table->foreignId('organization_id')
                    ->constrained('organizations')
                    ->restrictOnDelete();

                $table->foreignId('driver_quality_profile_id')
                    ->constrained('driver_quality_profiles')
                    ->restrictOnDelete();

                $table->string('scope_type', 32);
                $table->string('scope_key', 128);

                $table->foreignId('organization_relationship_id')
                    ->nullable()
                    ->constrained('organization_relationships')
                    ->restrictOnDelete();

                $table->foreignId('driver_organization_assignment_id')
                    ->nullable()
                    ->constrained('driver_organization_assignments')
                    ->restrictOnDelete();

                $table->date('valid_from');
                $table->date('valid_until')->nullable();

                $table->foreignId('created_by_user_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->timestamps();

                $table->unique(
                    [
                        'organization_id',
                        'scope_key',
                        'valid_from',
                    ],
                    'driver_quality_bindings_org_scope_from_unique',
                );

                $table->index(
                    [
                        'organization_id',
                        'scope_type',
                        'valid_from',
                        'valid_until',
                    ],
                    'driver_quality_bindings_org_scope_validity_index',
                );

                $table->index(
                    [
                        'driver_quality_profile_id',
                        'valid_from',
                        'valid_until',
                    ],
                    'driver_quality_bindings_profile_validity_index',
                );
            },
        );

        if ($driver !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
ALTER TABLE driver_quality_profiles
ADD CONSTRAINT driver_quality_profiles_code_check
CHECK (code <> '' AND code = btrim(code))
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_quality_profiles
ADD CONSTRAINT driver_quality_profiles_name_check
CHECK (name <> '' AND name = btrim(name))
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_quality_profiles
ADD CONSTRAINT driver_quality_profiles_status_check
CHECK (status IN ('active', 'archived'))
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_quality_profiles
ADD CONSTRAINT driver_quality_profiles_current_version_check
CHECK (current_version >= 1)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_quality_profile_versions
ADD CONSTRAINT driver_quality_profile_versions_number_check
CHECK (version_number >= 1 AND lock_version >= 1)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_quality_profile_versions
ADD CONSTRAINT driver_quality_profile_versions_status_check
CHECK (status IN ('draft', 'active', 'replaced', 'expired'))
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_quality_profile_versions
ADD CONSTRAINT driver_quality_profile_versions_method_check
CHECK (calculation_method IN ('processed_share', 'disabled'))
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_quality_profile_versions
ADD CONSTRAINT driver_quality_profile_versions_validity_check
CHECK (
    valid_until IS NULL
    OR valid_from IS NULL
    OR valid_until >= valid_from
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_quality_profile_versions
ADD CONSTRAINT driver_quality_profile_versions_month_boundary_check
CHECK (
    (
        valid_from IS NULL
        OR valid_from = date_trunc('month', valid_from)::date
    )
    AND
    (
        valid_until IS NULL
        OR valid_until = (
            date_trunc('month', valid_until)
            + interval '1 month - 1 day'
        )::date
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_quality_profile_versions
ADD CONSTRAINT driver_quality_profile_versions_activation_check
CHECK (
    status = 'draft'
    OR (
        valid_from IS NOT NULL
        AND activated_by_user_id IS NOT NULL
        AND activated_at IS NOT NULL
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_quality_profile_components
ADD CONSTRAINT driver_quality_profile_components_source_check
CHECK (
    source_code IN (
        'delivered_parcels',
        'redirected_parcels',
        'customer_rejected_parcels'
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_quality_profile_components
ADD CONSTRAINT driver_quality_profile_components_position_check
CHECK (position >= 1)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_quality_profile_bindings
ADD CONSTRAINT driver_quality_profile_bindings_validity_check
CHECK (valid_until IS NULL OR valid_until >= valid_from)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_quality_profile_bindings
ADD CONSTRAINT driver_quality_profile_bindings_month_boundary_check
CHECK (
    valid_from = date_trunc('month', valid_from)::date
    AND
    (
        valid_until IS NULL
        OR valid_until = (
            date_trunc('month', valid_until)
            + interval '1 month - 1 day'
        )::date
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_quality_profile_bindings
ADD CONSTRAINT driver_quality_profile_bindings_scope_check
CHECK (
    (
        scope_type = 'organization'
        AND scope_key = 'organization'
        AND organization_relationship_id IS NULL
        AND driver_organization_assignment_id IS NULL
    )
    OR
    (
        scope_type = 'carrier_relationship'
        AND organization_relationship_id IS NOT NULL
        AND driver_organization_assignment_id IS NULL
        AND scope_key = 'relationship:' || organization_relationship_id
    )
    OR
    (
        scope_type = 'driver_assignment'
        AND organization_relationship_id IS NULL
        AND driver_organization_assignment_id IS NOT NULL
        AND scope_key = 'assignment:' || driver_organization_assignment_id
    )
)
SQL);

        DB::statement(<<<'SQL'
CREATE EXTENSION IF NOT EXISTS btree_gist
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_quality_profile_versions
ADD CONSTRAINT driver_quality_profile_versions_period_exclusion
EXCLUDE USING gist (
    driver_quality_profile_id WITH =,
    daterange(valid_from, valid_until, '[]') WITH &&
)
WHERE (
    status IN ('active', 'replaced', 'expired')
    AND valid_from IS NOT NULL
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE driver_quality_profile_bindings
ADD CONSTRAINT driver_quality_profile_bindings_period_exclusion
EXCLUDE USING gist (
    organization_id WITH =,
    scope_key WITH =,
    daterange(valid_from, valid_until, '[]') WITH &&
)
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_quality_profile_bindings');
        Schema::dropIfExists('driver_quality_profile_components');
        Schema::dropIfExists('driver_quality_profile_versions');
        Schema::dropIfExists('driver_quality_profiles');
    }
};
