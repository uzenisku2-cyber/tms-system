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
                $table->string('license_number')
                    ->nullable()
                    ->change();
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'drivers',
            static function (Blueprint $table): void {
                $table->string('license_number')
                    ->nullable(false)
                    ->change();
            },
        );
    }
};