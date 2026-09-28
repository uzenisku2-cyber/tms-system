<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_availability_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations');
            $table->foreignId('driver_id')->constrained('drivers');
            $table->date('date');
            $table->string('availability', 16);
            $table->string('decision', 16)->default('pending');
            $table->string('timezone', 64)->default('Europe/Prague');
            $table->text('reason')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->foreignId('submitted_by_user_id')->constrained('users');
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'driver_id', 'date'], 'driver_availability_day_unique');
            $table->index(['organization_id', 'date', 'decision']);
        });

        Schema::create('driver_availability_day_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('availability_day_id')->constrained('driver_availability_days');
            $table->unsignedInteger('revision');
            $table->string('action', 16);
            $table->string('availability', 16);
            $table->string('decision', 16);
            $table->text('reason')->nullable();
            $table->foreignId('actor_user_id')->constrained('users');
            $table->timestamp('created_at');
            $table->unique(['availability_day_id', 'revision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_availability_day_events');
        Schema::dropIfExists('driver_availability_days');
    }
};
