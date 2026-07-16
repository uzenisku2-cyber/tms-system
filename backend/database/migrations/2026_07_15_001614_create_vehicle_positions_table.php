<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicle_positions', function (Blueprint $table) {

            $table->id();

            // vazba na jízdu / trip
            $table->unsignedBigInteger('trip_id');

            // GPS souřadnice
            $table->decimal('latitude', 10, 7);

            $table->decimal('longitude', 10, 7);

            // údaje o pohybu
            $table->integer('speed')
                ->nullable();

            $table->integer('heading')
                ->nullable();

            // čas uložení GPS bodu
            $table->timestamps();


            // rychlé hledání historie konkrétní jízdy
            $table->index('trip_id');

        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_positions');
    }
};