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
        Schema::create('vehicle_cost_allocation_financial_handoffs', function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->uuid('handoff_uid')->unique();
            $t->foreignId('vehicle_cost_allocation_id')->constrained('vehicle_cost_allocations')->restrictOnDelete();
            $t->uuid('allocation_uid');
            $t->unsignedInteger('allocation_revision');
            $t->foreignId('organization_context_id')->constrained('organizations')->restrictOnDelete();
            $t->string('status', 24);
            $t->decimal('net_amount', 14, 2);
            $t->decimal('vat_amount', 14, 2);
            $t->decimal('gross_amount', 14, 2);
            $t->char('currency', 3);
            $t->foreignId('prepared_by_user_id')->constrained('users')->restrictOnDelete();
            $t->timestamp('prepared_at');
            $t->unsignedInteger('revision');
            $t->boolean('financial_automation_performed')->default(false);
            $t->timestamps();
            $t->unique(['vehicle_cost_allocation_id', 'allocation_revision'], 'vehicle_cost_allocation_handoff_source_unique');
            $t->index(['organization_context_id', 'status', 'prepared_at'], 'vehicle_cost_allocation_handoff_operational_index');
        });
        Schema::create('vehicle_cost_allocation_financial_handoff_instructions', function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('financial_handoff_id')->constrained('vehicle_cost_allocation_financial_handoffs')->restrictOnDelete();
            $t->foreignId('vehicle_cost_allocation_line_id')->constrained('vehicle_cost_allocation_lines')->restrictOnDelete();
            $t->uuid('line_uid');
            $t->unsignedInteger('sequence_number');
            $t->string('settlement_mode', 32);
            $t->string('destination_type', 32);
            $t->string('responsible_party_type', 24);
            $t->foreignId('responsible_organization_id')->nullable();
            $t->foreign('responsible_organization_id', 'vcafhi_responsible_org_fk')
                ->references('id')->on('organizations')->restrictOnDelete();
            $t->foreignId('responsible_user_id')->nullable();
            $t->foreign('responsible_user_id', 'vcafhi_responsible_user_fk')
                ->references('id')->on('users')->restrictOnDelete();
            $t->string('external_party_name')->nullable();
            $t->decimal('net_amount', 14, 2);
            $t->decimal('vat_amount', 14, 2);
            $t->decimal('gross_amount', 14, 2);
            $t->char('currency', 3);
            $t->string('vat_treatment', 32);
            $t->boolean('requires_invoice');
            $t->boolean('bank_matching_eligible')->default(false);
            $t->string('execution_status', 24)->default('pending');
            $t->unsignedInteger('revision');
            $t->timestamps();
            $t->unique(['financial_handoff_id', 'sequence_number'], 'vehicle_cost_allocation_handoff_instruction_sequence_unique');
        });
        Schema::create('vehicle_cost_allocation_financial_handoff_events', function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('financial_handoff_id')->constrained('vehicle_cost_allocation_financial_handoffs')->restrictOnDelete();
            $t->string('event_type', 32);
            $t->json('evidence');
            $t->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->timestamp('occurred_at');
            $t->unique(['financial_handoff_id', 'revision'], 'vehicle_cost_allocation_handoff_event_revision_unique');
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE vehicle_cost_allocation_financial_handoffs ADD CONSTRAINT vehicle_cost_allocation_handoff_values_check CHECK (status IN ('prepared','cancelled') AND net_amount >= 0 AND vat_amount >= 0 AND gross_amount = net_amount + vat_amount AND revision >= 1 AND financial_automation_performed = false)");
            DB::statement("ALTER TABLE vehicle_cost_allocation_financial_handoff_instructions ADD CONSTRAINT vehicle_cost_allocation_handoff_instruction_values_check CHECK (destination_type IN ('billing_document','settlement_deduction','repair_fund','receivable_tracking','information','manual_review') AND execution_status = 'pending' AND bank_matching_eligible = false AND revision >= 1)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_cost_allocation_financial_handoff_events');
        Schema::dropIfExists('vehicle_cost_allocation_financial_handoff_instructions');
        Schema::dropIfExists('vehicle_cost_allocation_financial_handoffs');
    }
};
