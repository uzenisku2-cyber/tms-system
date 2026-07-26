<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{

    public function up(): void
    {

        Schema::table('trips', function (Blueprint $table) {


            $table->decimal(
                'origin_lat',
                10,
                7
            )
            ->nullable();



            $table->decimal(
                'origin_lng',
                10,
                7
            )
            ->nullable();



            $table->decimal(
                'destination_lat',
                10,
                7
            )
            ->nullable();



            $table->decimal(
                'destination_lng',
                10,
                7
            )
            ->nullable();



            $table->decimal(
                'distance_km',
                8,
                2
            )
            ->nullable();


        });

    }





    public function down(): void
    {

        Schema::table('trips', function (Blueprint $table) {


            $table->dropColumn([

                'origin_lat',

                'origin_lng',

                'destination_lat',

                'destination_lng',

                'distance_km',

            ]);


        });

    }

};