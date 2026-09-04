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
        Schema::create('fuel_transaction_settlement_applications', static function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $t->foreignId('fuel_transaction_id')->unique()->constrained('fuel_transactions')->restrictOnDelete();
            $t->foreignId('fuel_transaction_settlement_eligibility_id')->constrained('fuel_transaction_settlement_eligibilities')->restrictOnDelete();
            $t->unsignedInteger('eligibility_revision');
            $t->unsignedInteger('reconciliation_revision');
            $t->foreignId('fuel_card_settlement_policy_id')->constrained('fuel_card_settlement_policies')->restrictOnDelete();
            $t->string('settlement_target', 32);
            $t->foreignId('target_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $t->foreignId('target_driver_id')->nullable()->constrained('drivers')->restrictOnDelete();
            $t->string('discount_beneficiary', 32)->nullable();
            $t->string('amount_basis', 32);
            $t->string('vat_mode', 40)->nullable();
            $t->decimal('applied_amount', 16, 6);
            $t->char('currency', 3);
            $t->foreignId('financial_calculation_id')->nullable()->constrained('financial_calculations')->restrictOnDelete();
            $t->string('status', 32);
            $t->unsignedInteger('revision')->default(1);
            $t->foreignId('applied_by_user_id')->constrained('users')->restrictOnDelete();
            $t->timestamp('applied_at');
            $t->foreignId('reversed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('reversed_at')->nullable();
            $t->text('reversal_reason')->nullable();
            $t->timestamps();
            $t->index(['owner_organization_id', 'status'], 'fuel_settlement_app_owner_status_index');
            $t->index(['target_driver_id', 'status'], 'fuel_settlement_app_driver_status_index');
            $t->index(['target_organization_id', 'status'], 'fuel_settlement_app_target_org_status_index');
        });
        Schema::create('fuel_transaction_settlement_application_events', static function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('fuel_transaction_settlement_application_id')->constrained('fuel_transaction_settlement_applications')->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->string('event_type', 32);
            $t->string('from_status', 32)->nullable();
            $t->string('to_status', 32);
            $t->foreignId('acted_by_user_id')->constrained('users')->restrictOnDelete();
            $t->text('reason')->nullable();
            $t->json('metadata')->nullable();
            $t->timestamp('occurred_at');
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['fuel_transaction_settlement_application_id', 'revision'], 'fuel_settlement_app_event_revision_unique');
            $t->index(['event_type', 'occurred_at'], 'fuel_settlement_app_event_type_time_index');
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE fuel_transaction_settlement_applications ADD CONSTRAINT fuel_settlement_app_status_check CHECK (status IN ('applied','reversed') AND revision >= 1)");
            DB::statement('ALTER TABLE fuel_transaction_settlement_applications ADD CONSTRAINT fuel_settlement_app_target_check CHECK ((target_driver_id IS NOT NULL AND target_organization_id IS NULL) OR (target_driver_id IS NULL AND target_organization_id IS NOT NULL))');
            DB::statement("ALTER TABLE fuel_transaction_settlement_application_events ADD CONSTRAINT fuel_settlement_app_event_type_check CHECK (event_type IN ('applied','reversed') AND revision >= 1)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_transaction_settlement_application_events');
        Schema::dropIfExists('fuel_transaction_settlement_applications');
    }
};
