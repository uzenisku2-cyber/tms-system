<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();

            $table->string('registration_number')->unique();
            $table->string('vin')->unique();
            $table->string('manufacturer');
            $table->string('model');
            $table->year('year')->nullable();
            $table->string('fuel_type')->nullable();
            $table->unsignedInteger('mileage')->default(0);
            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
