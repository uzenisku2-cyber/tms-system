<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::table('vehicles', function (Blueprint $table) {

            if (! Schema::hasColumn('vehicles', 'vehicle_size')) {

                $table->string('vehicle_size')
                    ->nullable()
                    ->after('vehicle_type');

            }

            if (! Schema::hasColumn('vehicles', 'manufacturer_logo')) {

                $table->string('manufacturer_logo')
                    ->nullable()
                    ->after('icon');

            }

            if (! Schema::hasColumn('vehicles', 'body_style')) {

                $table->string('body_style')
                    ->nullable()
                    ->after('manufacturer_logo');

            }

        });

    }

    public function down(): void
    {

        Schema::table('vehicles', function (Blueprint $table) {

            $columns = [];

            foreach ([
                'vehicle_size',
                'manufacturer_logo',
                'body_style',
            ] as $column) {

                if (Schema::hasColumn('vehicles', $column)) {
                    $columns[] = $column;
                }

            }

            if (count($columns)) {
                $table->dropColumn($columns);
            }

        });

    }
};
