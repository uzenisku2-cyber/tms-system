<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{


    public function up(): void
    {

        Schema::create('alerts', function (Blueprint $table) {


            $table->id();


            $table->foreignId('trip_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();



            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();



            $table->string('type');


            $table->string('severity')
                ->default('info');



            $table->text('message');



            $table->timestamp('read_at')
                ->nullable();



            $table->timestamps();


        });

    }



    public function down(): void
    {

        Schema::dropIfExists('alerts');

    }


};