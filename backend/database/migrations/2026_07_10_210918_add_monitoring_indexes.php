<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{

    public function up(): void
    {

        Schema::table('trip_locations', function (Blueprint $table) {

            $table->index([
                'trip_id',
                'created_at'
            ]);

        });



        Schema::table('alerts', function (Blueprint $table) {

            $table->index([
                'trip_id',
                'resolved_at'
            ]);


            $table->index([
                'type',
                'resolved_at'
            ]);

        });

    }



    public function down(): void
    {

        Schema::table('trip_locations', function (Blueprint $table) {

            $table->dropIndex([
                'trip_id',
                'created_at'
            ]);

        });



        Schema::table('alerts', function (Blueprint $table) {

            $table->dropIndex([
                'trip_id',
                'resolved_at'
            ]);


            $table->dropIndex([
                'type',
                'resolved_at'
            ]);

        });

    }

};