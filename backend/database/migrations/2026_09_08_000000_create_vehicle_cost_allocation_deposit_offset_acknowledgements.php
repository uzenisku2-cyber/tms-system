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
        Schema::create('vehicle_cost_allocation_deposit_offset_acknowledgements', function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('financial_handoff_instruction_id');
            $t->foreignId('organization_context_id');
            $t->uuid('idempotency_key');
            $t->unsignedInteger('instruction_revision');
            $t->string('responsible_party_type', 24);
            $t->foreignId('responsible_organization_id')->nullable();
            $t->foreignId('responsible_user_id')->nullable();
            $t->decimal('net_amount', 16, 2);
            $t->decimal('vat_amount', 16, 2);
            $t->decimal('gross_amount', 16, 2);
            $t->char('currency', 3);
            $t->string('payment_method', 24);
            $t->string('payment_reference', 120)->nullable();
            $t->text('evidence_note');
            $t->string('vat_disposition', 32);
            $t->string('status', 24);
            $t->foreignId('acknowledged_by_user_id');
            $t->timestamp('acknowledged_at');
            $t->unsignedInteger('revision');
            $t->timestamps();
            $t->foreign('financial_handoff_instruction_id', 'vcadoa_instruction_fk')->references('id')->on('vehicle_cost_allocation_financial_handoff_instructions')->restrictOnDelete();
            $t->foreign('organization_context_id', 'vcadoa_organization_fk')->references('id')->on('organizations')->restrictOnDelete();
            $t->foreign('responsible_organization_id', 'vcadoa_responsible_org_fk')->references('id')->on('organizations')->restrictOnDelete();
            $t->foreign('responsible_user_id', 'vcadoa_responsible_user_fk')->references('id')->on('users')->restrictOnDelete();
            $t->foreign('acknowledged_by_user_id', 'vcadoa_actor_fk')->references('id')->on('users')->restrictOnDelete();
            $t->unique('financial_handoff_instruction_id', 'vcadoa_instruction_unique');
            $t->unique(['organization_context_id', 'idempotency_key'], 'vcadoa_org_idempotency_unique');
        });
        Schema::create('vehicle_cost_allocation_deposit_offset_events', function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('deposit_offset_acknowledgement_id');
            $t->string('event_type', 48);
            $t->json('evidence');
            $t->foreignId('actor_user_id');
            $t->unsignedInteger('revision');
            $t->timestamp('occurred_at');
            $t->foreign('deposit_offset_acknowledgement_id', 'vcadoe_acknowledgement_fk')->references('id')->on('vehicle_cost_allocation_deposit_offset_acknowledgements')->restrictOnDelete();
            $t->foreign('actor_user_id', 'vcadoe_actor_fk')->references('id')->on('users')->restrictOnDelete();
            $t->unique(['deposit_offset_acknowledgement_id', 'revision'], 'vcadoe_ack_revision_unique');
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE vehicle_cost_allocation_deposit_offset_acknowledgements ADD CONSTRAINT vcadoa_values_check CHECK (instruction_revision >= 1 AND responsible_party_type IN ('organization','driver') AND ((responsible_party_type = 'organization' AND responsible_organization_id IS NOT NULL AND responsible_user_id IS NULL) OR (responsible_party_type = 'driver' AND responsible_organization_id IS NULL AND responsible_user_id IS NOT NULL)) AND net_amount >= 0 AND vat_amount >= 0 AND gross_amount = net_amount + vat_amount AND payment_method IN ('cash','bank_transfer','card','other') AND vat_disposition IN ('offset','repair_fund_pending') AND status = 'acknowledged' AND revision >= 1)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_cost_allocation_deposit_offset_events');
        Schema::dropIfExists('vehicle_cost_allocation_deposit_offset_acknowledgements');
    }
};
