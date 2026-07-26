<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{

    public function up(): void
    {

        Schema::create('trip_locations', function (Blueprint $table) {

            $table->id();


            $table->foreignId('trip_id')
                ->constrained()
                ->cascadeOnDelete();


            $table->decimal(
                'latitude',
                10,
                7
            );


            $table->decimal(
                'longitude',
                10,
                7
            );


            $table->integer('speed')
                ->nullable();


            $table->integer('heading')
                ->nullable();


            $table->timestamps();

        });

    }


    public function down(): void
    {

        Schema::dropIfExists('trip_locations');

    }

};