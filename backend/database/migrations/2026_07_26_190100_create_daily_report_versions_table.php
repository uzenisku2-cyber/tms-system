<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_report_versions', static function (Blueprint $table): void {
            $table->id();

            $table->foreignId('daily_report_id')
                ->constrained('daily_reports')
                ->restrictOnDelete();

            $table->unsignedInteger('version_number');
            $table->jsonb('snapshot');
            $table->jsonb('changed_fields');

            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('change_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                [
                    'daily_report_id',
                    'version_number',
                ],
                'daily_report_versions_report_version_unique',
            );

            $table->index(
                [
                    'daily_report_id',
                    'created_at',
                ],
                'daily_report_versions_report_created_index',
            );
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
ALTER TABLE daily_report_versions
ADD CONSTRAINT daily_report_versions_number_check
CHECK (version_number >= 1)
SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_report_versions');
    }
};
