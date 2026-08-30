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
        Schema::table('fuel_transactions', static function (Blueprint $table): void {
            $table->foreignId('actual_driver_id')->nullable()->after('driver_id')->constrained('drivers')->restrictOnDelete();
            $table->foreignId('actual_driver_organization_assignment_id')->nullable()->after('actual_driver_id')->constrained('driver_organization_assignments')->restrictOnDelete();
            $table->unsignedInteger('driver_attribution_revision')->default(0)->after('actual_driver_organization_assignment_id');
            $table->index(['owner_organization_id', 'actual_driver_id', 'occurred_at'], 'fuel_transactions_actual_driver_index');
        });

        Schema::create('fuel_transaction_driver_attributions', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('fuel_transaction_id')->constrained('fuel_transactions')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->foreignId('previous_driver_id')->nullable()->constrained('drivers')->restrictOnDelete();
            $table->foreignId('new_driver_id')->constrained('drivers')->restrictOnDelete();
            $table->foreignId('previous_driver_organization_assignment_id')->nullable()->constrained('driver_organization_assignments')->restrictOnDelete();
            $table->foreignId('new_driver_organization_assignment_id')->constrained('driver_organization_assignments')->restrictOnDelete();
            $table->string('reason', 1000);
            $table->foreignId('corrected_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('corrected_at');
            $table->timestamps();
            $table->unique(['fuel_transaction_id', 'revision'], 'fuel_transaction_driver_attributions_revision_unique');
            $table->index(['new_driver_id', 'corrected_at'], 'fuel_driver_attributions_new_driver_index');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE fuel_transaction_driver_attributions ADD CONSTRAINT fuel_driver_attributions_revision_check CHECK (revision > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_transaction_driver_attributions');
        Schema::table('fuel_transactions', static function (Blueprint $table): void {
            $table->dropIndex('fuel_transactions_actual_driver_index');
            $table->dropColumn(['actual_driver_id', 'actual_driver_organization_assignment_id', 'driver_attribution_revision']);
        });
    }
};
