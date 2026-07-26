<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{


    public function up(): void
    {

        Schema::table('alerts', function (Blueprint $table) {


            if (! Schema::hasColumn('alerts', 'resolved_at')) {

                $table->timestamp('resolved_at')
                    ->nullable()
                    ->after('read_at');

            }



            if (! Schema::hasColumn('alerts', 'resolved_by')) {

                $table->foreignId('resolved_by')
                    ->nullable()
                    ->after('resolved_at')
                    ->constrained('users')
                    ->nullOnDelete();

            }


        });

    }





    public function down(): void
    {

        Schema::table('alerts', function (Blueprint $table) {


            if (Schema::hasColumn('alerts', 'resolved_by')) {

                $table->dropForeign([
                    'resolved_by'
                ]);

            }



            $columns = [];


            if (Schema::hasColumn('alerts', 'resolved_at')) {

                $columns[] = 'resolved_at';

            }



            if (Schema::hasColumn('alerts', 'resolved_by')) {

                $columns[] = 'resolved_by';

            }



            if (! empty($columns)) {

                $table->dropColumn($columns);

            }


        });

    }


};