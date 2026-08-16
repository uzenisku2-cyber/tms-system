<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_supervisory_scopes', static function (Blueprint $table): void {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained('organizations');

            $table->foreignId('supervisor_user_id')
                ->constrained('users');

            $table->string('scope_type', 32);

            $table->foreignId('target_organization_id')
                ->nullable()
                ->constrained('organizations');

            $table->foreignId('target_driver_id')
                ->nullable()
                ->constrained('drivers');

            $table->foreignId('organization_relationship_id')
                ->nullable()
                ->constrained('organization_relationships');

            $table->date('valid_from');
            $table->date('valid_until')->nullable();

            $table->foreignId('created_by_user_id')
                ->constrained('users');

            $table->foreignId('ended_by_user_id')
                ->nullable()
                ->constrained('users');

            $table->text('end_reason')->nullable();

            $table->timestamps();

            $table->index(
                ['organization_id', 'supervisor_user_id'],
                'driver_supervisory_scopes_org_supervisor_index'
            );

            $table->index(
                ['target_organization_id'],
                'driver_supervisory_scopes_target_org_index'
            );

            $table->index(
                ['target_driver_id'],
                'driver_supervisory_scopes_target_driver_index'
            );

            $table->index(
                ['organization_relationship_id'],
                'driver_supervisory_scopes_relationship_index'
            );
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            <<<'SQL'
ALTER TABLE driver_supervisory_scopes
ADD CONSTRAINT driver_supervisory_scopes_scope_type_check
CHECK (scope_type IN ('organization', 'driver'))
SQL
        );

        DB::statement(
            <<<'SQL'
ALTER TABLE driver_supervisory_scopes
ADD CONSTRAINT driver_supervisory_scopes_target_check
CHECK (
    (
        scope_type = 'organization'
        AND target_organization_id IS NOT NULL
        AND target_driver_id IS NULL
    )
    OR
    (
        scope_type = 'driver'
        AND target_organization_id IS NULL
        AND target_driver_id IS NOT NULL
    )
)
SQL
        );

        DB::statement(
            <<<'SQL'
ALTER TABLE driver_supervisory_scopes
ADD CONSTRAINT driver_supervisory_scopes_validity_check
CHECK (
    valid_until IS NULL
    OR valid_until >= valid_from
)
SQL
        );

        DB::statement(
            <<<'SQL'
ALTER TABLE driver_supervisory_scopes
ADD CONSTRAINT driver_supervisory_scopes_organization_relationship_check
CHECK (
    scope_type <> 'organization'
    OR (
        target_organization_id = organization_id
        AND organization_relationship_id IS NULL
    )
    OR (
        target_organization_id <> organization_id
        AND organization_relationship_id IS NOT NULL
    )
)
SQL
        );

        DB::statement(
            <<<'SQL'
CREATE UNIQUE INDEX driver_supervisory_scopes_open_organization_unique
ON driver_supervisory_scopes (
    organization_id,
    supervisor_user_id,
    target_organization_id
)
WHERE scope_type = 'organization'
  AND valid_until IS NULL
SQL
        );

        DB::statement(
            <<<'SQL'
CREATE UNIQUE INDEX driver_supervisory_scopes_open_driver_unique
ON driver_supervisory_scopes (
    organization_id,
    supervisor_user_id,
    target_driver_id
)
WHERE scope_type = 'driver'
  AND valid_until IS NULL
SQL
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_supervisory_scopes');
    }
};
