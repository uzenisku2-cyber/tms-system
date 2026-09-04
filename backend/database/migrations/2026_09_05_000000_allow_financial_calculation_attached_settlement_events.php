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

        DB::statement('ALTER TABLE fuel_transaction_settlement_application_events DROP CONSTRAINT fuel_settlement_app_event_type_check');
        DB::statement("ALTER TABLE fuel_transaction_settlement_application_events ADD CONSTRAINT fuel_settlement_app_event_type_check CHECK (event_type IN ('applied','financial_calculation_attached','reversed') AND revision >= 1)");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("DELETE FROM fuel_transaction_settlement_application_events WHERE event_type = 'financial_calculation_attached'");
        DB::statement('ALTER TABLE fuel_transaction_settlement_application_events DROP CONSTRAINT fuel_settlement_app_event_type_check');
        DB::statement("ALTER TABLE fuel_transaction_settlement_application_events ADD CONSTRAINT fuel_settlement_app_event_type_check CHECK (event_type IN ('applied','reversed') AND revision >= 1)");
    }
};
