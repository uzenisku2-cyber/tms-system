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
            'daily_report_form_configurations',
            static function (Blueprint $table): void {
                $table->id();

                $table->foreignId('organization_id')
                    ->constrained('organizations')
                    ->restrictOnDelete();

                $table->unsignedInteger('version');

                $table->date('valid_from');
                $table->date('valid_until')->nullable();

                $table->json('fields');

                $table->foreignId('created_by_user_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->foreignId('ended_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->timestamps();

                $table->unique(
                    ['organization_id', 'version'],
                    'daily_report_form_org_version_unique',
                );

                $table->index(
                    ['organization_id', 'valid_from', 'valid_until'],
                    'daily_report_form_validity_index',
                );
            },
        );

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
ALTER TABLE daily_report_form_configurations
ADD CONSTRAINT daily_report_form_period_check
CHECK (
    valid_until IS NULL
    OR valid_until >= valid_from
)
SQL);

            DB::statement(<<<'SQL'
CREATE UNIQUE INDEX daily_report_form_one_open_unique
ON daily_report_form_configurations (organization_id)
WHERE valid_until IS NULL
SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'daily_report_form_configurations',
        );
    }
};
