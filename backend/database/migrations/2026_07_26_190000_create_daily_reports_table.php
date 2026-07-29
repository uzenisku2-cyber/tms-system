<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_reports', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();

            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->restrictOnDelete();

            $table->foreignId('trip_id')
                ->nullable()
                ->constrained('trips')
                ->nullOnDelete();

            $table->foreignId('performed_by_driver_id')
                ->constrained('drivers')
                ->restrictOnDelete();

            $table->foreignId('vehicle_id')
                ->nullable()
                ->constrained('vehicles')
                ->nullOnDelete();

            $table->foreignId('entered_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('route_number', 100);
            $table->string('route_number_normalized', 100);
            $table->date('service_date');

            $table->string('status', 32)->default('draft');
            $table->string('entry_method', 32)->default('driver');
            $table->boolean('entered_on_behalf')->default(false);
            $table->timestamp('completion_confirmed_at')->nullable();

            $table->unsignedInteger('delivered_parcels')->nullable();
            $table->unsignedInteger('redirected_parcels')->nullable();
            $table->unsignedInteger('undelivered_parcels')->nullable();

            $table->decimal('planned_km', 10, 2)->nullable();
            $table->decimal('actual_km', 10, 2)->nullable();
            $table->string('actual_km_source', 32)->nullable();
            $table->text('operational_notes')->nullable();

            $table->unsignedInteger('current_version')->default(1);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('review_started_at')->nullable();

            $table->foreignId('reviewed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(
                [
                    'organization_id',
                    'service_date',
                    'route_number_normalized',
                ],
                'daily_reports_org_date_route_unique',
            );

            $table->index(
                [
                    'organization_id',
                    'status',
                    'service_date',
                ],
                'daily_reports_org_status_date_index',
            );

            $table->index(
                [
                    'organization_id',
                    'performed_by_driver_id',
                    'service_date',
                ],
                'daily_reports_org_driver_date_index',
            );

            $table->index(
                'trip_id',
                'daily_reports_trip_id_index',
            );
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
ALTER TABLE daily_reports
ADD CONSTRAINT daily_reports_status_check
CHECK (
    status IN (
        'draft',
        'submitted',
        'under_review',
        'correction_requested',
        'corrected',
        'approved',
        'closed'
    )
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE daily_reports
ADD CONSTRAINT daily_reports_entry_method_check
CHECK (
    entry_method IN (
        'driver',
        'delegated',
        'authorized_import'
    )
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE daily_reports
ADD CONSTRAINT daily_reports_entry_actor_check
CHECK (
    (entry_method = 'delegated' AND entered_on_behalf = TRUE)
    OR
    (entry_method <> 'delegated' AND entered_on_behalf = FALSE)
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE daily_reports
ADD CONSTRAINT daily_reports_route_number_check
CHECK (
    route_number <> ''
    AND route_number = btrim(route_number)
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE daily_reports
ADD CONSTRAINT daily_reports_route_normalized_check
CHECK (
    route_number_normalized <> ''
    AND route_number_normalized = lower(btrim(route_number))
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE daily_reports
ADD CONSTRAINT daily_reports_parcel_counts_check
CHECK (
    (delivered_parcels IS NULL OR delivered_parcels >= 0)
    AND
    (redirected_parcels IS NULL OR redirected_parcels >= 0)
    AND
    (undelivered_parcels IS NULL OR undelivered_parcels >= 0)
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE daily_reports
ADD CONSTRAINT daily_reports_kilometres_check
CHECK (
    (planned_km IS NULL OR planned_km >= 0)
    AND
    (actual_km IS NULL OR actual_km >= 0)
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE daily_reports
ADD CONSTRAINT daily_reports_actual_km_source_check
CHECK (
    actual_km_source IS NULL
    OR actual_km_source IN (
        'delivery_application',
        'manual',
        'authorized_import',
        'other'
    )
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE daily_reports
ADD CONSTRAINT daily_reports_current_version_check
CHECK (current_version >= 1)
SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
