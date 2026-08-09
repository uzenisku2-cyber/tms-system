<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'daily_reports',
            static function (Blueprint $table): void {
                $table->foreignId(
                    'daily_report_form_configuration_id',
                )
                    ->nullable()
                    ->after('service_date')
                    ->constrained(
                        'daily_report_form_configurations',
                    )
                    ->restrictOnDelete();

                $table->json('custom_field_values')
                    ->nullable()
                    ->after(
                        'daily_report_form_configuration_id',
                    );

                $table->index(
                    'daily_report_form_configuration_id',
                    'daily_reports_form_configuration_index',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'daily_reports',
            static function (Blueprint $table): void {
                $table->dropIndex(
                    'daily_reports_form_configuration_index',
                );

                $table->dropConstrainedForeignId(
                    'daily_report_form_configuration_id',
                );

                $table->dropColumn(
                    'custom_field_values',
                );
            },
        );
    }
};
