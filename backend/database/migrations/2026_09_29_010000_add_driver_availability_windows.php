<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_availability_days', function (Blueprint $table): void {
            $table->json('windows')->nullable();
        });
        Schema::table('driver_availability_day_events', function (Blueprint $table): void {
            $table->json('windows')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('driver_availability_day_events', function (Blueprint $table): void {
            $table->dropColumn('windows');
        });
        Schema::table('driver_availability_days', function (Blueprint $table): void {
            $table->dropColumn('windows');
        });
    }
};
