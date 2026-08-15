<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = true;

    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if (! in_array($driver, ['pgsql', 'sqlite'], true)) {
            throw new RuntimeException(
                sprintf(
                    'Unsupported database driver [%s] for price-list management authority migration.',
                    $driver,
                ),
            );
        }

        Schema::table(
            'price_lists',
            static function (Blueprint $table): void {
                $table
                    ->unsignedBigInteger('managed_by_organization_id')
                    ->nullable();

                $table->index(
                    'managed_by_organization_id',
                    'price_lists_managed_by_organization_index',
                );
            },
        );

        DB::statement(
            <<<'SQL'
UPDATE price_lists
SET managed_by_organization_id = owner_organization_id
WHERE managed_by_organization_id IS NULL
SQL
        );

        Schema::table(
            'price_lists',
            static function (Blueprint $table): void {
                $table
                    ->foreign('managed_by_organization_id')
                    ->references('id')
                    ->on('organizations')
                    ->restrictOnDelete();
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'price_lists',
            static function (Blueprint $table): void {
                $table->dropForeign(
                    ['managed_by_organization_id'],
                );

                $table->dropIndex(
                    'price_lists_managed_by_organization_index',
                );

                $table->dropColumn(
                    'managed_by_organization_id',
                );
            },
        );
    }
};
