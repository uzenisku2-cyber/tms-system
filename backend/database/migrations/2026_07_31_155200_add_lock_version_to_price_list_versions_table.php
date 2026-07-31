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
        Schema::table(
            'price_list_versions',
            static function (Blueprint $table): void {
                $table->unsignedInteger('lock_version')
                    ->default(1);
            },
        );

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
ALTER TABLE price_list_versions
ADD CONSTRAINT price_list_versions_lock_version_check
CHECK (lock_version >= 1)
SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
ALTER TABLE price_list_versions
DROP CONSTRAINT IF EXISTS price_list_versions_lock_version_check
SQL);
        }

        Schema::table(
            'price_list_versions',
            static function (Blueprint $table): void {
                $table->dropColumn('lock_version');
            },
        );
    }
};
