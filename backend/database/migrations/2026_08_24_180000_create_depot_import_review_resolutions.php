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
            throw new RuntimeException("Unsupported database driver [$driver].");
        }

        Schema::create('depot_import_review_resolutions', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('depot_import_batch_id')->constrained('depot_import_batches')->restrictOnDelete();
            $table->foreignId('depot_import_row_id')->unique()->constrained('depot_import_rows')->restrictOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->string('resolution_type', 64);
            $table->foreignId('corrected_driver_id')->nullable()->constrained('drivers')->restrictOnDelete();
            $table->foreignId('corrected_driver_organization_assignment_id')->nullable()->constrained('driver_organization_assignments')->restrictOnDelete();
            $table->text('reason');
            $table->foreignId('resolved_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['organization_id', 'resolution_type', 'created_at'], 'depot_review_resolutions_org_type_created_index');
        });

        if ($driver !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
ALTER TABLE depot_import_review_resolutions
ADD CONSTRAINT depot_review_resolutions_type_check
CHECK (resolution_type IN ('driver_attribution_corrected', 'zero_value_ignored'))
SQL);
        DB::statement(<<<'SQL'
ALTER TABLE depot_import_review_resolutions
ADD CONSTRAINT depot_review_resolutions_driver_check
CHECK (
    (resolution_type = 'driver_attribution_corrected' AND corrected_driver_id IS NOT NULL AND corrected_driver_organization_assignment_id IS NOT NULL)
    OR
    (resolution_type = 'zero_value_ignored' AND corrected_driver_id IS NULL AND corrected_driver_organization_assignment_id IS NULL)
)
SQL);
        DB::statement('ALTER TABLE depot_import_events DROP CONSTRAINT depot_import_events_type_check');
        DB::statement(<<<'SQL'
ALTER TABLE depot_import_events
ADD CONSTRAINT depot_import_events_type_check
CHECK (event_type IN (
    'draft_created',
    'source_driver_mapped',
    'import_finalized',
    'import_cancelled',
    'driver_attribution_corrected',
    'zero_value_ignored',
    'review_resolution_reverted'
))
SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE depot_import_events DROP CONSTRAINT depot_import_events_type_check');
            DB::statement(<<<'SQL'
ALTER TABLE depot_import_events
ADD CONSTRAINT depot_import_events_type_check
CHECK (event_type IN ('draft_created', 'source_driver_mapped', 'import_finalized', 'import_cancelled'))
SQL);
        }

        Schema::dropIfExists('depot_import_review_resolutions');
    }
};
