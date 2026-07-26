<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {

            $table->foreignId('cancelled_by')
                ->nullable()
                ->after('finished_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('cancelled_at')
                ->nullable();

            $table->text('cancel_reason')
                ->nullable();

        });
    }


    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {

            $table->dropForeign([
                'cancelled_by'
            ]);

            $table->dropColumn([
                'cancelled_by',
                'cancelled_at',
                'cancel_reason',
            ]);

        });
    }
};