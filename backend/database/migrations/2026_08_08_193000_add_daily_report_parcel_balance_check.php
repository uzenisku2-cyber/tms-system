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

        DB::statement(<<<'SQL'
ALTER TABLE daily_reports
ADD CONSTRAINT daily_reports_parcel_balance_check
CHECK (
    loaded_parcels IS NULL
    OR delivered_parcels IS NULL
    OR redirected_parcels IS NULL
    OR undelivered_parcels IS NULL
    OR (
        loaded_parcels
        - delivered_parcels
        - redirected_parcels
        - undelivered_parcels
    ) >= 0
)
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
ALTER TABLE daily_reports
DROP CONSTRAINT IF EXISTS daily_reports_parcel_balance_check
SQL);
    }
};
