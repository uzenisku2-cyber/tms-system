<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {

            $table->string('vehicle_type')
                ->nullable()
                ->after('model');

            $table->string('color')
                ->nullable()
                ->after('vehicle_type');

            $table->string('icon')
                ->nullable()
                ->after('color');

        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {

            $table->dropColumn([
                'vehicle_type',
                'color',
                'icon',
            ]);

        });
    }
};
