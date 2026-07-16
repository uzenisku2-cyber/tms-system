<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_positions', function (Blueprint $table) {

            $table->foreignId('vehicle_id')
                ->nullable()
                ->after('id')
                ->constrained('vehicles')
                ->cascadeOnDelete();

        });
    }


    public function down(): void
    {
        Schema::table('vehicle_positions', function (Blueprint $table) {

            $table->dropForeign([
                'vehicle_id'
            ]);

            $table->dropColumn('vehicle_id');

        });
    }
};