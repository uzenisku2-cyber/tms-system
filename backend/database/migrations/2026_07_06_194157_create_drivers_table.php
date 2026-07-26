<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('first_name');

            $table->string('last_name');

            $table->string('phone')
                ->nullable();

            $table->string('email')
                ->nullable();

            $table->string('license_number')
                ->unique();

            $table->string('license_category')
                ->nullable();

            $table->boolean('active')
                ->default(true);

            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};