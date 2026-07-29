<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_report_events', static function (Blueprint $table): void {
            $table->id();

            $table->foreignId('daily_report_id')
                ->constrained('daily_reports')
                ->restrictOnDelete();

            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->restrictOnDelete();

            $table->string('event_type', 64);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();

            $table->foreignId('acted_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('reason')->nullable();
            $table->jsonb('affected_fields')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                [
                    'daily_report_id',
                    'created_at',
                ],
                'daily_report_events_report_created_index',
            );

            $table->index(
                [
                    'organization_id',
                    'event_type',
                    'created_at',
                ],
                'daily_report_events_org_type_created_index',
            );
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
ALTER TABLE daily_report_events
ADD CONSTRAINT daily_report_events_type_check
CHECK (
    event_type IN (
        'created',
        'delegated_entry_recorded',
        'updated',
        'submitted',
        'review_started',
        'correction_requested',
        'corrected',
        'resubmitted',
        'approved',
        'closed'
    )
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE daily_report_events
ADD CONSTRAINT daily_report_events_from_status_check
CHECK (
    from_status IS NULL
    OR from_status IN (
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
ALTER TABLE daily_report_events
ADD CONSTRAINT daily_report_events_to_status_check
CHECK (
    to_status IS NULL
    OR to_status IN (
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
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_report_events');
    }
};
