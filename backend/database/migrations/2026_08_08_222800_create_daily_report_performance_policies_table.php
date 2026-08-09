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
        Schema::create(
            'daily_report_performance_policies',
            static function (Blueprint $table): void {
                $table->id();

                $table->foreignId('organization_id')
                    ->constrained('organizations')
                    ->restrictOnDelete();

                $table->string('scope_key', 128);

                $table->string('route_number', 100)
                    ->nullable();

                $table->string('route_number_normalized', 100)
                    ->nullable();

                $table->decimal(
                    'redirected_max_percent',
                    5,
                    2,
                )->nullable();

                $table->decimal(
                    'kilometre_deviation_max_percent',
                    5,
                    2,
                )->nullable();

                $table->decimal(
                    'delivered_address_min_percent',
                    5,
                    2,
                )->nullable();

                $table->decimal(
                    'rejected_max_percent',
                    5,
                    2,
                )->nullable();

                $table->decimal(
                    'not_delivered_max_percent',
                    5,
                    2,
                )->nullable();

                $table->foreignId('updated_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->unique(
                    [
                        'organization_id',
                        'scope_key',
                    ],
                    'daily_report_perf_policy_org_scope_unique',
                );

                $table->index(
                    [
                        'organization_id',
                        'route_number_normalized',
                    ],
                    'daily_report_perf_policy_org_route_index',
                );
            },
        );

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
ALTER TABLE daily_report_performance_policies
ADD CONSTRAINT daily_report_perf_policy_scope_check
CHECK (
    (
        scope_key = '__organization__'
        AND route_number IS NULL
        AND route_number_normalized IS NULL
    )
    OR
    (
        scope_key <> '__organization__'
        AND route_number IS NOT NULL
        AND route_number_normalized IS NOT NULL
        AND scope_key = 'route:' || route_number_normalized
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE daily_report_performance_policies
ADD CONSTRAINT daily_report_perf_policy_percentage_check
CHECK (
    (
        redirected_max_percent IS NULL
        OR redirected_max_percent BETWEEN 0 AND 100
    )
    AND
    (
        kilometre_deviation_max_percent IS NULL
        OR kilometre_deviation_max_percent BETWEEN 0 AND 100
    )
    AND
    (
        delivered_address_min_percent IS NULL
        OR delivered_address_min_percent BETWEEN 0 AND 100
    )
    AND
    (
        rejected_max_percent IS NULL
        OR rejected_max_percent BETWEEN 0 AND 100
    )
    AND
    (
        not_delivered_max_percent IS NULL
        OR not_delivered_max_percent BETWEEN 0 AND 100
    )
)
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'daily_report_performance_policies',
        );
    }
};