<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', static function (Blueprint $table): void {
            $table->string('status', 32)->default('active');
            $table->timestamp('status_changed_at')->nullable();

            $table->index('status', 'drivers_status_index');
        });

        DB::table('drivers')
            ->where('active', true)
            ->update(['status' => 'active']);

        DB::table('drivers')
            ->where('active', false)
            ->update(['status' => 'inactive']);

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE drivers
                 ADD CONSTRAINT drivers_status_check
                 CHECK (status IN ('active', 'suspended', 'inactive'))"
            );
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE drivers
                 DROP CONSTRAINT IF EXISTS drivers_status_check'
            );
        }

        Schema::table('drivers', static function (Blueprint $table): void {
            $table->dropIndex('drivers_status_index');
            $table->dropColumn([
                'status',
                'status_changed_at',
            ]);
        });
    }
};
