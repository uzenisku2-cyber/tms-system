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
        Schema::create('fuel_cards', static function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $t->string('provider', 32);
            $t->string('provider_card_identifier', 128);
            $t->string('masked_card_number', 64);
            $t->string('label')->nullable();
            $t->string('status', 32)->default('active');
            $t->date('valid_from');
            $t->date('expires_at')->nullable();
            $t->char('currency', 3)->default('CZK');
            $t->jsonb('purchase_restrictions')->nullable();
            $t->unsignedInteger('lock_version')->default(1);
            $t->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['owner_organization_id', 'provider', 'provider_card_identifier'], 'fuel_cards_owner_provider_identifier_unique');
            $t->index(['owner_organization_id', 'status', 'expires_at'], 'fuel_cards_owner_status_expiry_index');
        });
        Schema::create('fuel_card_assignments', static function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('fuel_card_id')->constrained('fuel_cards')->restrictOnDelete();
            $t->foreignId('responsible_organization_id')->constrained('organizations')->restrictOnDelete();
            $t->foreignId('driver_id')->nullable()->constrained('drivers')->restrictOnDelete();
            $t->foreignId('vehicle_id')->nullable()->constrained('vehicles')->restrictOnDelete();
            $t->string('assignment_type', 32);
            $t->string('status', 32)->default('active');
            $t->timestamp('valid_from');
            $t->timestamp('valid_until')->nullable();
            $t->text('usage_restrictions')->nullable();
            $t->text('reason');
            $t->foreignId('assigned_by_user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('ended_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->index(['fuel_card_id', 'valid_from', 'valid_until'], 'fuel_card_assignments_card_period_index');
        });
        Schema::create('fuel_card_settlement_policies', static function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $t->foreignId('fuel_card_id')->nullable()->constrained('fuel_cards')->restrictOnDelete();
            $t->string('settlement_target', 32);
            $t->string('discount_beneficiary', 32);
            $t->string('amount_basis', 32);
            $t->string('vat_mode', 40);
            $t->date('valid_from');
            $t->date('valid_until')->nullable();
            $t->text('reason');
            $t->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->index(['owner_organization_id', 'valid_from', 'valid_until'], 'fuel_card_policies_owner_period_index');
        });
        Schema::create('fuel_card_events', static function (Blueprint $t): void {
            $t->id();
            $t->foreignId('fuel_card_id')->constrained('fuel_cards')->restrictOnDelete();
            $t->foreignId('fuel_card_assignment_id')->nullable()->constrained('fuel_card_assignments')->restrictOnDelete();
            $t->foreignId('fuel_card_settlement_policy_id')->nullable()->constrained('fuel_card_settlement_policies')->restrictOnDelete();
            $t->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
            $t->string('event_type', 64);
            $t->foreignId('acted_by_user_id')->constrained('users')->restrictOnDelete();
            $t->text('reason')->nullable();
            $t->jsonb('before_payload')->nullable();
            $t->jsonb('after_payload')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE fuel_cards ADD CONSTRAINT fuel_cards_values_check CHECK (provider IN ('ORLEN','MOL','OTHER') AND status IN ('active','blocked','expired','retired') AND lock_version >= 1 AND (expires_at IS NULL OR expires_at >= valid_from) AND masked_card_number !~ '^[0-9]{10,}$')");
            DB::statement("ALTER TABLE fuel_card_assignments ADD CONSTRAINT fuel_card_assignments_values_check CHECK (assignment_type IN ('organization','driver','vehicle','driver_vehicle','temporary','shared_pool') AND status IN ('active','ended','cancelled') AND (valid_until IS NULL OR valid_until >= valid_from))");
            DB::statement("ALTER TABLE fuel_card_settlement_policies ADD CONSTRAINT fuel_card_policies_values_check CHECK (settlement_target IN ('carrier','driver') AND discount_beneficiary IN ('carrier','driver') AND amount_basis IN ('net','gross') AND vat_mode IN ('counterparty_tax_profile','not_applicable') AND (valid_until IS NULL OR valid_until >= valid_from) AND ((settlement_target='driver' AND vat_mode='not_applicable') OR settlement_target='carrier'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_card_events');
        Schema::dropIfExists('fuel_card_settlement_policies');
        Schema::dropIfExists('fuel_card_assignments');
        Schema::dropIfExists('fuel_cards');
    }
};
