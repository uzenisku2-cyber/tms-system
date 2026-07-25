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
        Schema::create('organization_memberships', static function (Blueprint $table): void {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('relationship_type', 32);
            $table->string('status', 32)->default('invited');
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();

            $table->index(
                ['organization_id', 'status'],
                'organization_memberships_organization_status_index'
            );

            $table->index(
                ['user_id', 'status'],
                'organization_memberships_user_status_index'
            );
        });

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement(
                'ALTER TABLE organization_memberships
                 ADD CONSTRAINT organization_memberships_validity_check
                 CHECK (
                     valid_until IS NULL
                     OR valid_from IS NULL
                     OR valid_until >= valid_from
                 )'
            );
        }

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement(
                'CREATE UNIQUE INDEX organization_memberships_open_unique
                 ON organization_memberships (
                     organization_id,
                     user_id,
                     relationship_type
                 )
                 WHERE valid_until IS NULL'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_memberships');
    }
};
