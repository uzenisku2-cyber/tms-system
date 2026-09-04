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
        Schema::create('fuel_transaction_settlement_eligibilities', static function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $t->foreignId('fuel_transaction_id')->unique()->constrained('fuel_transactions')->restrictOnDelete();
            $t->string('status', 32);
            $t->string('result_code', 64);
            $t->foreignId('fuel_card_settlement_policy_id')->nullable()->constrained('fuel_card_settlement_policies')->restrictOnDelete();
            $t->unsignedInteger('reconciliation_revision')->default(0);
            $t->string('settlement_target', 32)->nullable();
            $t->foreignId('target_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $t->foreignId('target_driver_id')->nullable()->constrained('drivers')->restrictOnDelete();
            $t->string('discount_beneficiary', 32)->nullable();
            $t->string('amount_basis', 32)->nullable();
            $t->string('vat_mode', 40)->nullable();
            $t->decimal('base_amount', 16, 6)->nullable();
            $t->char('currency', 3)->nullable();
            $t->unsignedInteger('revision')->default(0);
            $t->timestamp('evaluated_at')->nullable();
            $t->timestamps();
            $t->index(['owner_organization_id', 'status'], 'fuel_settlement_eligibility_owner_status_index');
        });
        Schema::create('fuel_transaction_settlement_eligibility_evaluations', static function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('fuel_transaction_settlement_eligibility_id')->constrained('fuel_transaction_settlement_eligibilities')->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->string('evaluation_version', 32);
            $t->string('status', 32);
            $t->string('result_code', 64);
            $t->foreignId('fuel_card_settlement_policy_id')->nullable()->constrained('fuel_card_settlement_policies')->restrictOnDelete();
            $t->unsignedInteger('reconciliation_revision');
            $t->string('settlement_target', 32)->nullable();
            $t->foreignId('target_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $t->foreignId('target_driver_id')->nullable()->constrained('drivers')->restrictOnDelete();
            $t->string('discount_beneficiary', 32)->nullable();
            $t->string('amount_basis', 32)->nullable();
            $t->string('vat_mode', 40)->nullable();
            $t->decimal('base_amount', 16, 6)->nullable();
            $t->char('currency', 3)->nullable();
            $t->json('evidence');
            $t->foreignId('evaluated_by_user_id')->constrained('users')->restrictOnDelete();
            $t->timestamp('evaluated_at');
            $t->timestamps();
            $t->unique(['fuel_transaction_settlement_eligibility_id', 'revision'], 'fuel_settlement_eligibility_evaluation_revision_unique');
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE fuel_transaction_settlement_eligibilities ADD CONSTRAINT fuel_settlement_eligibility_status_check CHECK (status IN ('eligible','blocked') AND revision >= 0)");
            DB::statement("ALTER TABLE fuel_transaction_settlement_eligibility_evaluations ADD CONSTRAINT fuel_settlement_evaluation_status_check CHECK (status IN ('eligible','blocked') AND revision >= 1)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_transaction_settlement_eligibility_evaluations');
        Schema::dropIfExists('fuel_transaction_settlement_eligibilities');
    }
};
