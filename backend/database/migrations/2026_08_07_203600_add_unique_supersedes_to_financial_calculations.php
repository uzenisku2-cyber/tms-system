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
            'financial_calculations',
            static function (Blueprint $table): void {
                $table->unique(
                    'supersedes_calculation_id',
                    'fin_calcs_supersedes_unique',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'financial_calculations',
            static function (Blueprint $table): void {
                $table->dropUnique(
                    'fin_calcs_supersedes_unique',
                );
            },
        );
    }
};
