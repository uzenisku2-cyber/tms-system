<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{

    public function up(): void
    {
        Schema::create('trip_pods', function (Blueprint $table) {

            $table->id();


            $table->foreignId('trip_id')
                ->constrained()
                ->cascadeOnDelete();


            $table->string('recipient');


            $table->text('note')
                ->nullable();


            $table->timestamp('delivered_at');


            $table->foreignId('delivered_by')
                ->constrained('users');


            $table->timestamps();

        });
    }



    public function down(): void
    {
        Schema::dropIfExists('trip_pods');
    }

};