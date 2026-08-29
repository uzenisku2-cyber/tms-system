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
        Schema::table('fuel_cards', function (Blueprint $table): void {
            $table->string('provider_status', 32)->default('unknown')->after('status');
            $table->timestamp('provider_status_verified_at')->nullable()->after('provider_status');
            $table->text('provider_status_note')->nullable()->after('provider_status_verified_at');
            $table->index(
                ['owner_organization_id', 'provider', 'provider_status'],
                'fuel_cards_provider_inventory_status_index',
            );
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE fuel_cards ADD CONSTRAINT fuel_cards_provider_status_check CHECK (provider_status IN ('active', 'temporarily_blocked', 'blocked', 'cancelled', 'unknown', 'verification_required'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE fuel_cards DROP CONSTRAINT IF EXISTS fuel_cards_provider_status_check');
        }

        Schema::table('fuel_cards', function (Blueprint $table): void {
            $table->dropIndex('fuel_cards_provider_inventory_status_index');
            $table->dropColumn(['provider_status', 'provider_status_verified_at', 'provider_status_note']);
        });
    }
};
