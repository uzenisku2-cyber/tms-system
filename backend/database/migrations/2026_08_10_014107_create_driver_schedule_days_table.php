<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_schedule_days', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('driver_id')
                ->constrained('drivers')
                ->cascadeOnDelete();

            $table->date('date');
            $table->string('status', 16);

            $table->timestamps();

            $table->unique(
                ['driver_id', 'date'],
                'driver_schedule_days_driver_date_unique',
            );

            $table->index(
                ['date', 'status'],
                'driver_schedule_days_date_status_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_schedule_days');
    }
};
