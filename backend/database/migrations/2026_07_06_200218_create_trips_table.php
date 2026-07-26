<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();


            $table->foreignId('driver_id')
                ->constrained('drivers')
                ->cascadeOnDelete();


            $table->foreignId('vehicle_id')
                ->constrained('vehicles')
                ->cascadeOnDelete();


            $table->string('origin');

            $table->string('destination');


            $table->string('status')
                ->default('planned');


            $table->timestamp('scheduled_at')
                ->nullable();

            $table->timestamp('started_at')
                ->nullable();

            $table->timestamp('finished_at')
                ->nullable();


            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};