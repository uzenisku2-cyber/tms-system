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
        Schema::create('financial_calculation_events', static function (Blueprint $table): void {
            $table->id();

            $table->foreignId('financial_calculation_id')
                ->constrained('financial_calculations')
                ->restrictOnDelete();

            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->restrictOnDelete();

            $table->string('event_type', 32);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);

            $table->foreignId('acted_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('reason')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                [
                    'financial_calculation_id',
                    'created_at',
                ],
                'fin_calc_events_calc_created_index',
            );

            $table->index(
                [
                    'organization_id',
                    'event_type',
                ],
                'fin_calc_events_org_type_index',
            );
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
ALTER TABLE financial_calculation_events
ADD CONSTRAINT fin_calc_events_type_check
CHECK (
    event_type IN (
        'calculated',
        'review_started',
        'approved',
        'closed',
        'cancelled'
    )
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE financial_calculation_events
ADD CONSTRAINT fin_calc_events_from_status_check
CHECK (
    from_status IS NULL
    OR from_status IN (
        'calculated',
        'under_review',
        'approved',
        'closed',
        'cancelled'
    )
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE financial_calculation_events
ADD CONSTRAINT fin_calc_events_to_status_check
CHECK (
    to_status IN (
        'calculated',
        'under_review',
        'approved',
        'closed',
        'cancelled'
    )
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE financial_calculation_events
ADD CONSTRAINT fin_calc_events_transition_check
CHECK (
    (
        event_type = 'calculated'
        AND from_status IS NULL
        AND to_status = 'calculated'
    )
    OR
    (
        event_type = 'review_started'
        AND from_status = 'calculated'
        AND to_status = 'under_review'
    )
    OR
    (
        event_type = 'approved'
        AND from_status = 'under_review'
        AND to_status = 'approved'
    )
    OR
    (
        event_type = 'closed'
        AND from_status = 'approved'
        AND to_status = 'closed'
    )
    OR
    (
        event_type = 'cancelled'
        AND from_status IN (
            'calculated',
            'under_review'
        )
        AND to_status = 'cancelled'
    )
)
SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_calculation_events');
    }
};
