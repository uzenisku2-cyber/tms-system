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
        Schema::table(
            'daily_reports',
            static function (Blueprint $table): void {
                $table->time('departure_time')->nullable();
                $table->time('arrival_time')->nullable();

                $table->unsignedInteger(
                    'loaded_parcels',
                )->nullable();

                $table->decimal(
                    'surcharge_amount',
                    12,
                    2,
                )->default(0);
            },
        );

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
ALTER TABLE daily_reports
ADD CONSTRAINT daily_reports_loaded_parcels_check
CHECK (
    loaded_parcels IS NULL
    OR loaded_parcels >= 0
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE daily_reports
ADD CONSTRAINT daily_reports_surcharge_amount_check
CHECK (
    surcharge_amount >= 0
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE daily_reports
ADD CONSTRAINT daily_reports_surcharge_note_check
CHECK (
    surcharge_amount = 0
    OR btrim(COALESCE(operational_notes, '')) <> ''
)
SQL);
        }
    }

    public function down(): void
    {
        if (
            DB::getDriverName() === 'pgsql'
            && Schema::hasTable('daily_reports')
        ) {
            DB::statement(
                'ALTER TABLE daily_reports '.
                'DROP CONSTRAINT IF EXISTS daily_reports_surcharge_note_check',
            );

            DB::statement(
                'ALTER TABLE daily_reports '.
                'DROP CONSTRAINT IF EXISTS daily_reports_surcharge_amount_check',
            );

            DB::statement(
                'ALTER TABLE daily_reports '.
                'DROP CONSTRAINT IF EXISTS daily_reports_loaded_parcels_check',
            );
        }

        Schema::table(
            'daily_reports',
            static function (Blueprint $table): void {
                $table->dropColumn([
                    'departure_time',
                    'arrival_time',
                    'loaded_parcels',
                    'surcharge_amount',
                ]);
            },
        );
    }
};
