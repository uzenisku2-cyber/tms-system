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
        Schema::create('organization_relationships', static function (Blueprint $table): void {
            $table->id();

            $table->foreignId('source_organization_id')
                ->constrained('organizations')
                ->restrictOnDelete();

            $table->foreignId('target_organization_id')
                ->constrained('organizations')
                ->restrictOnDelete();

            $table->string('relationship_type', 32);
            $table->string('status', 32)->default('active');
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();

            $table->index(
                ['source_organization_id', 'status'],
                'organization_relationships_source_status_index'
            );

            $table->index(
                ['target_organization_id', 'status'],
                'organization_relationships_target_status_index'
            );
        });

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement(
                'ALTER TABLE organization_relationships
                 ADD CONSTRAINT organization_relationships_distinct_organizations_check
                 CHECK (source_organization_id <> target_organization_id)'
            );

            DB::statement(
                'ALTER TABLE organization_relationships
                 ADD CONSTRAINT organization_relationships_validity_check
                 CHECK (
                     valid_until IS NULL
                     OR valid_from IS NULL
                     OR valid_until >= valid_from
                 )'
            );
        }

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement(
                'CREATE UNIQUE INDEX organization_relationships_open_unique
                 ON organization_relationships (
                     source_organization_id,
                     target_organization_id,
                     relationship_type
                 )
                 WHERE valid_until IS NULL'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_relationships');
    }
};
