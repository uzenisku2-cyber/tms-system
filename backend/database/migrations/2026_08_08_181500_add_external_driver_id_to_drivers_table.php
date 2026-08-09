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
            'drivers',
            static function (Blueprint $table): void {
                $table->string(
                    'external_driver_id',
                    32,
                )
                    ->nullable()
                    ->after('last_name');

                $table->unique(
                    'external_driver_id',
                    'drivers_external_driver_id_unique',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'drivers',
            static function (Blueprint $table): void {
                $table->dropUnique(
                    'drivers_external_driver_id_unique',
                );

                $table->dropColumn(
                    'external_driver_id',
                );
            },
        );
    }
};