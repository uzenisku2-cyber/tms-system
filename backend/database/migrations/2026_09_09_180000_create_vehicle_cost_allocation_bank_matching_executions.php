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
        Schema::create('vehicle_cost_allocation_bank_matching_executions', function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('bank_matching_handoff_id');
            $t->foreignId('bank_transaction_evidence_id');
            $t->foreignId('billing_document_id');
            $t->foreignId('organization_context_id');
            $t->uuid('idempotency_key');
            $t->unsignedInteger('handoff_revision');
            $t->unsignedInteger('bank_transaction_evidence_revision');
            $t->decimal('matched_amount', 14, 2);
            $t->char('currency', 3);
            $t->date('effective_date');
            $t->string('allocation_source', 32);
            $t->string('status', 24);
            $t->text('reason');
            $t->foreignId('executed_by_user_id');
            $t->timestamp('executed_at');
            $t->unsignedInteger('revision');
            $t->timestamps();
            $t->foreign('bank_matching_handoff_id', 'vcabme_handoff_fk')->references('id')->on('vehicle_cost_allocation_bank_matching_handoffs')->restrictOnDelete();
            $t->foreign('bank_transaction_evidence_id', 'vcabme_evidence_fk')->references('id')->on('bank_transaction_evidence')->restrictOnDelete();
            $t->foreign('billing_document_id', 'vcabme_billing_document_fk')->references('id')->on('billing_documents')->restrictOnDelete();
            $t->foreign('organization_context_id', 'vcabme_org_context_fk')->references('id')->on('organizations')->restrictOnDelete();
            $t->foreign('executed_by_user_id', 'vcabme_executed_by_fk')->references('id')->on('users')->restrictOnDelete();
            $t->unique('bank_matching_handoff_id', 'vcabme_handoff_unique');
            $t->unique(['organization_context_id', 'idempotency_key'], 'vcabme_org_idempotency_unique');
            $t->index(['bank_transaction_evidence_id', 'status'], 'vcabme_evidence_status_index');
            $t->index(['billing_document_id', 'status'], 'vcabme_document_status_index');
        });

        Schema::create('vehicle_cost_allocation_bank_matching_execution_events', function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('bank_matching_execution_id');
            $t->string('event_type', 64);
            $t->json('evidence');
            $t->foreignId('actor_user_id');
            $t->unsignedInteger('revision');
            $t->timestamp('occurred_at');
            $t->timestamps();
            $t->foreign('bank_matching_execution_id', 'vcabmee_execution_fk')->references('id')->on('vehicle_cost_allocation_bank_matching_executions')->restrictOnDelete();
            $t->foreign('actor_user_id', 'vcabmee_actor_fk')->references('id')->on('users')->restrictOnDelete();
            $t->unique(['bank_matching_execution_id', 'revision'], 'vcabmee_execution_revision_unique');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE vehicle_cost_allocation_bank_matching_executions ADD CONSTRAINT vcabme_values_check CHECK (matched_amount > 0 AND allocation_source = 'manual_execution' AND status = 'executed' AND handoff_revision >= 1 AND bank_transaction_evidence_revision >= 1 AND revision >= 1)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_cost_allocation_bank_matching_execution_events');
        Schema::dropIfExists('vehicle_cost_allocation_bank_matching_executions');
    }
};
