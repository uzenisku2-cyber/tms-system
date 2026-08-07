<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            'ALTER TABLE financial_calculation_events '.
            'DROP CONSTRAINT IF EXISTS fin_calc_events_transition_check'
        );

        DB::statement(
            'ALTER TABLE financial_calculation_events '.
            'DROP CONSTRAINT IF EXISTS fin_calc_events_type_check'
        );

        DB::statement(<<<'SQL'
ALTER TABLE financial_calculation_events
ADD CONSTRAINT fin_calc_events_type_check
CHECK (
    event_type IN (
        'calculated',
        'recalculated',
        'review_started',
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
        event_type = 'recalculated'
        AND from_status = 'approved'
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

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        if (
            DB::table('financial_calculation_events')
                ->where('event_type', 'recalculated')
                ->exists()
        ) {
            throw new RuntimeException(
                'Cannot remove recalculated financial-calculation event support while recalculated events exist.',
            );
        }

        DB::statement(
            'ALTER TABLE financial_calculation_events '.
            'DROP CONSTRAINT IF EXISTS fin_calc_events_transition_check'
        );

        DB::statement(
            'ALTER TABLE financial_calculation_events '.
            'DROP CONSTRAINT IF EXISTS fin_calc_events_type_check'
        );

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
};
