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
        Schema::create('vehicle_cost_allocation_handoff_executions', function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('financial_handoff_instruction_id');
            $t->foreignId('billing_document_id');
            $t->foreignId('organization_context_id');
            $t->uuid('idempotency_key');
            $t->unsignedInteger('instruction_revision');
            $t->string('status', 24);
            $t->decimal('net_amount', 16, 2);
            $t->decimal('vat_amount', 16, 2);
            $t->decimal('gross_amount', 16, 2);
            $t->char('currency', 3);
            $t->unsignedInteger('vat_rate_basis_points');
            $t->foreignId('executed_by_user_id');
            $t->timestamp('executed_at');
            $t->unsignedInteger('revision');
            $t->timestamps();
            $t->foreign('financial_handoff_instruction_id', 'vcafhe_instruction_fk')->references('id')->on('vehicle_cost_allocation_financial_handoff_instructions')->restrictOnDelete();
            $t->foreign('billing_document_id', 'vcafhe_billing_document_fk')->references('id')->on('billing_documents')->restrictOnDelete();
            $t->foreign('organization_context_id', 'vcafhe_organization_fk')->references('id')->on('organizations')->restrictOnDelete();
            $t->foreign('executed_by_user_id', 'vcafhe_actor_fk')->references('id')->on('users')->restrictOnDelete();
            $t->unique('financial_handoff_instruction_id', 'vcafhe_instruction_unique');
            $t->unique(['organization_context_id', 'idempotency_key'], 'vcafhe_org_idempotency_unique');
        });
        Schema::create('vehicle_cost_allocation_handoff_execution_events', function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('handoff_execution_id');
            $t->string('event_type', 40);
            $t->json('evidence');
            $t->foreignId('actor_user_id');
            $t->unsignedInteger('revision');
            $t->timestamp('occurred_at');
            $t->foreign('handoff_execution_id', 'vcafhee_execution_fk')->references('id')->on('vehicle_cost_allocation_handoff_executions')->restrictOnDelete();
            $t->foreign('actor_user_id', 'vcafhee_actor_fk')->references('id')->on('users')->restrictOnDelete();
            $t->unique(['handoff_execution_id', 'revision'], 'vcafhee_execution_revision_unique');
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE vehicle_cost_allocation_handoff_executions ADD CONSTRAINT vcafhe_values_check CHECK (status = 'executed' AND instruction_revision >= 1 AND net_amount >= 0 AND vat_amount >= 0 AND gross_amount = net_amount + vat_amount AND vat_rate_basis_points <= 10000 AND revision >= 1)");
            DB::statement('ALTER TABLE billing_documents DROP CONSTRAINT billing_documents_values_check');
            DB::statement("ALTER TABLE billing_documents ADD CONSTRAINT billing_documents_values_check CHECK (document_type IN ('customer_invoice','external_carrier_settlement','driver_remuneration') AND status IN ('draft','under_review','approved','closed','cancelled') AND vat_treatment IN ('standard','not_applicable') AND vat_status_snapshot IN ('payer','non_payer') AND period_until >= period_from AND net_amount >= 0 AND vat_amount >= 0 AND gross_amount = net_amount + vat_amount AND ((vat_treatment = 'standard' AND vat_status_snapshot = 'payer' AND vat_rate IS NOT NULL) OR (vat_treatment = 'not_applicable' AND vat_status_snapshot = 'non_payer' AND vat_rate IS NULL AND vat_amount = 0)) AND ((document_type = 'customer_invoice' AND ((counterparty_organization_id IS NOT NULL AND driver_id IS NULL) OR (counterparty_organization_id IS NULL AND driver_id IS NOT NULL))) OR (document_type = 'external_carrier_settlement' AND counterparty_organization_id IS NOT NULL AND driver_id IS NULL) OR (document_type = 'driver_remuneration' AND counterparty_organization_id IS NULL AND driver_id IS NOT NULL AND vat_treatment = 'not_applicable')))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE billing_documents DROP CONSTRAINT billing_documents_values_check');
            DB::statement("ALTER TABLE billing_documents ADD CONSTRAINT billing_documents_values_check CHECK (document_type IN ('customer_invoice','external_carrier_settlement','driver_remuneration') AND status IN ('draft','under_review','approved','closed','cancelled') AND vat_treatment IN ('standard','not_applicable') AND vat_status_snapshot IN ('payer','non_payer') AND period_until >= period_from AND net_amount >= 0 AND vat_amount >= 0 AND gross_amount = net_amount + vat_amount AND ((vat_treatment = 'standard' AND vat_status_snapshot = 'payer' AND vat_rate IS NOT NULL) OR (vat_treatment = 'not_applicable' AND vat_status_snapshot = 'non_payer' AND vat_rate IS NULL AND vat_amount = 0)) AND ((document_type IN ('customer_invoice','external_carrier_settlement') AND counterparty_organization_id IS NOT NULL AND driver_id IS NULL) OR (document_type = 'driver_remuneration' AND counterparty_organization_id IS NULL AND driver_id IS NOT NULL AND vat_treatment = 'not_applicable')))");
        }Schema::dropIfExists('vehicle_cost_allocation_handoff_execution_events');
        Schema::dropIfExists('vehicle_cost_allocation_handoff_executions');
    }
};
