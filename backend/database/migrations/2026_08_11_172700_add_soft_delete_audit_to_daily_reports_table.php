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
                $table->softDeletes();

                $table->foreignId(
                    'deleted_by_user_id',
                )
                    ->nullable()
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->text(
                    'deletion_reason',
                )->nullable();
            },
        );

        Schema::table(
            'daily_reports',
            static function (Blueprint $table): void {
                $table->dropUnique(
                    'daily_reports_org_date_route_unique',
                );
            },
        );

        DB::statement(
            <<<'SQL'
CREATE UNIQUE INDEX daily_reports_org_date_route_unique
ON daily_reports (
    organization_id,
    service_date,
    route_number_normalized
)
WHERE deleted_at IS NULL
SQL
        );
    }

    public function down(): void
    {
        DB::statement(
            'DROP INDEX IF EXISTS daily_reports_org_date_route_unique',
        );

        Schema::table(
            'daily_reports',
            static function (Blueprint $table): void {
                $table->dropForeign([
                    'deleted_by_user_id',
                ]);

                $table->dropColumn([
                    'deleted_by_user_id',
                    'deletion_reason',
                ]);

                $table->dropSoftDeletes();
            },
        );

        Schema::table(
            'daily_reports',
            static function (Blueprint $table): void {
                $table->unique(
                    [
                        'organization_id',
                        'service_date',
                        'route_number_normalized',
                    ],
                    'daily_reports_org_date_route_unique',
                );
            },
        );
    }
};
