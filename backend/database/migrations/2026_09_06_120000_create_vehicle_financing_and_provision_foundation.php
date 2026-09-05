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
        Schema::create('vehicle_provision_agreements', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->uuid('agreement_uid');
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('organization_context_id')->constrained('organizations')->restrictOnDelete();
            $table->string('provider_type', 24);
            $table->foreignId('provider_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $table->foreignId('provider_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('recipient_type', 24);
            $table->foreignId('recipient_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('provision_mode', 32);
            $table->string('agreement_number')->nullable();
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->string('status', 24);
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['agreement_uid', 'revision'], 'vehicle_provision_agreement_revision_unique');
            $table->index(['vehicle_id', 'status', 'valid_from', 'valid_until'], 'vehicle_provision_effective_index');
        });

        Schema::create('vehicle_provision_prices', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->uuid('price_uid');
            $table->foreignId('vehicle_provision_agreement_id')->constrained('vehicle_provision_agreements')->restrictOnDelete();
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3);
            $table->string('billing_period', 24);
            $table->string('billing_mode', 32);
            $table->string('vat_mode', 32);
            $table->unsignedInteger('vat_rate_basis_points')->nullable();
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['price_uid', 'revision'], 'vehicle_provision_price_revision_unique');
            $table->index(['vehicle_provision_agreement_id', 'valid_from', 'valid_until'], 'vehicle_provision_price_effective_index');
        });

        Schema::create('vehicle_financing_agreements', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->uuid('financing_uid');
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('organization_context_id')->constrained('organizations')->restrictOnDelete();
            $table->string('financing_type', 32);
            $table->foreignId('financier_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $table->string('external_financier_name')->nullable();
            $table->string('debtor_type', 24);
            $table->foreignId('debtor_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $table->foreignId('debtor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('agreement_number')->nullable();
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->char('currency', 3);
            $table->decimal('total_amount', 14, 2)->nullable();
            $table->decimal('initial_payment_amount', 14, 2)->nullable();
            $table->decimal('residual_value_amount', 14, 2)->nullable();
            $table->string('status', 24);
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['financing_uid', 'revision'], 'vehicle_financing_agreement_revision_unique');
            $table->index(['vehicle_id', 'status', 'effective_from', 'effective_until'], 'vehicle_financing_effective_index');
        });

        Schema::create('vehicle_installment_schedules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->uuid('schedule_uid');
            $table->foreignId('vehicle_financing_agreement_id')->constrained('vehicle_financing_agreements')->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->unsignedInteger('installment_count');
            $table->decimal('planned_total_amount', 14, 2);
            $table->char('currency', 3);
            $table->string('frequency', 24);
            $table->string('status', 24);
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['schedule_uid', 'revision'], 'vehicle_installment_schedule_revision_unique');
        });

        Schema::create('vehicle_installments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->uuid('installment_uid');
            $table->foreignId('vehicle_installment_schedule_id')->constrained('vehicle_installment_schedules')->restrictOnDelete();
            $table->unsignedInteger('sequence_number');
            $table->date('due_on');
            $table->decimal('principal_amount', 14, 2);
            $table->decimal('finance_charge_amount', 14, 2)->default(0);
            $table->decimal('other_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2);
            $table->char('currency', 3);
            $table->string('status', 24);
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['installment_uid', 'revision'], 'vehicle_installment_revision_unique');
            $table->unique(['vehicle_installment_schedule_id', 'sequence_number', 'revision'], 'vehicle_installment_sequence_revision_unique');
            $table->index(['vehicle_installment_schedule_id', 'due_on', 'status'], 'vehicle_installment_due_index');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE vehicle_provision_agreements ADD CONSTRAINT vehicle_provision_agreement_values_check CHECK (provider_type IN ('organization','driver') AND ((provider_type = 'organization' AND provider_organization_id IS NOT NULL AND provider_user_id IS NULL) OR (provider_type = 'driver' AND provider_user_id IS NOT NULL AND provider_organization_id IS NULL)) AND recipient_type IN ('organization','driver') AND ((recipient_type = 'organization' AND recipient_organization_id IS NOT NULL AND recipient_user_id IS NULL) OR (recipient_type = 'driver' AND recipient_user_id IS NOT NULL AND recipient_organization_id IS NULL)) AND provision_mode IN ('own_vehicle','free_use','rental','operating_lease','finance_lease','purchase_installment') AND status IN ('draft','active','suspended','ended','cancelled') AND revision >= 1 AND (valid_until IS NULL OR valid_until >= valid_from))");
            DB::statement("ALTER TABLE vehicle_provision_prices ADD CONSTRAINT vehicle_provision_price_values_check CHECK (amount >= 0 AND billing_period IN ('one_time','daily','weekly','monthly','none') AND billing_mode IN ('invoice_required','deposit_offset','informational_only','manual_review') AND vat_mode IN ('standard_rate','not_applicable','pending_review') AND (vat_rate_basis_points IS NULL OR vat_rate_basis_points <= 10000) AND revision >= 1 AND (valid_until IS NULL OR valid_until >= valid_from))");
            DB::statement("ALTER TABLE vehicle_financing_agreements ADD CONSTRAINT vehicle_financing_agreement_values_check CHECK (financing_type IN ('operating_lease','finance_lease','purchase_installment','loan','other') AND debtor_type IN ('organization','driver') AND ((debtor_type = 'organization' AND debtor_organization_id IS NOT NULL AND debtor_user_id IS NULL) OR (debtor_type = 'driver' AND debtor_user_id IS NOT NULL AND debtor_organization_id IS NULL)) AND status IN ('draft','active','suspended','completed','terminated','cancelled') AND revision >= 1 AND (effective_until IS NULL OR effective_until >= effective_from) AND (total_amount IS NULL OR total_amount >= 0) AND (initial_payment_amount IS NULL OR initial_payment_amount >= 0) AND (residual_value_amount IS NULL OR residual_value_amount >= 0))");
            DB::statement("ALTER TABLE vehicle_installment_schedules ADD CONSTRAINT vehicle_installment_schedule_values_check CHECK (installment_count >= 1 AND planned_total_amount >= 0 AND frequency IN ('weekly','monthly','quarterly','annual','custom') AND status IN ('draft','active','replaced','completed','cancelled') AND revision >= 1 AND (ends_on IS NULL OR ends_on >= starts_on))");
            DB::statement("ALTER TABLE vehicle_installments ADD CONSTRAINT vehicle_installment_values_check CHECK (sequence_number >= 1 AND principal_amount >= 0 AND finance_charge_amount >= 0 AND other_amount >= 0 AND total_amount = principal_amount + finance_charge_amount + other_amount AND status IN ('planned','cancelled','replaced','waived') AND revision >= 1)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_installments');
        Schema::dropIfExists('vehicle_installment_schedules');
        Schema::dropIfExists('vehicle_financing_agreements');
        Schema::dropIfExists('vehicle_provision_prices');
        Schema::dropIfExists('vehicle_provision_agreements');
    }
};
