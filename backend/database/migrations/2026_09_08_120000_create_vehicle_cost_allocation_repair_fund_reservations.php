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
        Schema::create('vehicle_cost_allocation_repair_fund_reservations', function (Blueprint $t): void {
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
            $t->string('reserve_purpose', 32);
            $t->text('evidence_note');
            $t->string('status', 24);
            $t->foreignId('reserved_by_user_id');
            $t->timestamp('reserved_at');
            $t->unsignedInteger('revision');
            $t->timestamps();
            $t->foreign('financial_handoff_instruction_id', 'vcafrr_instruction_fk')->references('id')->on('vehicle_cost_allocation_financial_handoff_instructions')->restrictOnDelete();
            $t->foreign('organization_context_id', 'vcafrr_organization_fk')->references('id')->on('organizations')->restrictOnDelete();
            $t->foreign('responsible_organization_id', 'vcafrr_responsible_org_fk')->references('id')->on('organizations')->restrictOnDelete();
            $t->foreign('responsible_user_id', 'vcafrr_responsible_user_fk')->references('id')->on('users')->restrictOnDelete();
            $t->foreign('reserved_by_user_id', 'vcafrr_actor_fk')->references('id')->on('users')->restrictOnDelete();
            $t->unique('financial_handoff_instruction_id', 'vcafrr_instruction_unique');
            $t->unique(['organization_context_id', 'idempotency_key'], 'vcafrr_org_idempotency_unique');
        });
        Schema::create('vehicle_cost_allocation_repair_fund_events', function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('repair_fund_reservation_id');
            $t->string('event_type', 48);
            $t->json('evidence');
            $t->foreignId('actor_user_id');
            $t->unsignedInteger('revision');
            $t->timestamp('occurred_at');
            $t->foreign('repair_fund_reservation_id', 'vcafre_reservation_fk')->references('id')->on('vehicle_cost_allocation_repair_fund_reservations')->restrictOnDelete();
            $t->foreign('actor_user_id', 'vcafre_actor_fk')->references('id')->on('users')->restrictOnDelete();
            $t->unique(['repair_fund_reservation_id', 'revision'], 'vcafre_reservation_revision_unique');
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE vehicle_cost_allocation_repair_fund_reservations ADD CONSTRAINT vcafrr_values_check CHECK (instruction_revision >= 1 AND responsible_party_type IN ('organization','driver') AND ((responsible_party_type = 'organization' AND responsible_organization_id IS NOT NULL AND responsible_user_id IS NULL) OR (responsible_party_type = 'driver' AND responsible_organization_id IS NULL AND responsible_user_id IS NOT NULL)) AND net_amount >= 0 AND vat_amount >= 0 AND gross_amount = net_amount + vat_amount AND reserve_purpose IN ('vehicle_repair','damage_deductible','maintenance','other') AND status = 'reserved' AND revision >= 1)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_cost_allocation_repair_fund_events');
        Schema::dropIfExists('vehicle_cost_allocation_repair_fund_reservations');
    }
};
