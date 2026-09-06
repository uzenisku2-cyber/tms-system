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
        Schema::create('vehicle_cost_allocation_bank_matching_handoffs', function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('financial_handoff_instruction_id');
            $t->foreignId('billing_document_id');
            $t->foreignId('organization_context_id');
            $t->uuid('idempotency_key');
            $t->unsignedInteger('instruction_revision');
            $t->string('responsible_party_type', 32);
            $t->foreignId('responsible_organization_id')->nullable();
            $t->foreignId('responsible_user_id')->nullable();
            $t->string('bank_transaction_reference', 191);
            $t->string('bank_statement_reference', 191)->nullable();
            $t->date('booked_at');
            $t->decimal('evidence_amount', 14, 2);
            $t->char('currency', 3);
            $t->string('counterparty_name')->nullable();
            $t->text('evidence_note');
            $t->string('status', 24);
            $t->foreignId('prepared_by_user_id');
            $t->timestamp('prepared_at');
            $t->unsignedInteger('revision');
            $t->timestamps();
            $t->foreign('financial_handoff_instruction_id', 'vcabmh_instruction_fk')->references('id')->on('vehicle_cost_allocation_financial_handoff_instructions')->restrictOnDelete();
            $t->foreign('billing_document_id', 'vcabmh_billing_document_fk')->references('id')->on('billing_documents')->restrictOnDelete();
            $t->foreign('organization_context_id', 'vcabmh_org_context_fk')->references('id')->on('organizations')->restrictOnDelete();
            $t->foreign('responsible_organization_id', 'vcabmh_responsible_org_fk')->references('id')->on('organizations')->restrictOnDelete();
            $t->foreign('responsible_user_id', 'vcabmh_responsible_user_fk')->references('id')->on('users')->restrictOnDelete();
            $t->foreign('prepared_by_user_id', 'vcabmh_prepared_by_fk')->references('id')->on('users')->restrictOnDelete();
            $t->unique(['organization_context_id', 'idempotency_key'], 'vcabmh_org_idempotency_unique');
            $t->unique(['financial_handoff_instruction_id', 'bank_transaction_reference'], 'vcabmh_instruction_bank_ref_unique');
            $t->index(['organization_context_id', 'status', 'booked_at'], 'vcabmh_operational_index');
        });
        Schema::create('vehicle_cost_allocation_bank_matching_handoff_events', function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('bank_matching_handoff_id');
            $t->string('event_type', 48);
            $t->json('evidence');
            $t->foreignId('actor_user_id');
            $t->unsignedInteger('revision');
            $t->timestamp('occurred_at');
            $t->timestamps();
            $t->foreign('bank_matching_handoff_id', 'vcabmhe_handoff_fk')->references('id')->on('vehicle_cost_allocation_bank_matching_handoffs')->restrictOnDelete();
            $t->foreign('actor_user_id', 'vcabmhe_actor_fk')->references('id')->on('users')->restrictOnDelete();
            $t->unique(['bank_matching_handoff_id', 'revision'], 'vcabmhe_handoff_revision_unique');
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE vehicle_cost_allocation_bank_matching_handoffs ADD CONSTRAINT vcabmh_values_check CHECK (status = 'prepared' AND instruction_revision >= 1 AND evidence_amount > 0 AND revision >= 1 AND responsible_party_type IN ('organization','driver'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_cost_allocation_bank_matching_handoff_events');
        Schema::dropIfExists('vehicle_cost_allocation_bank_matching_handoffs');
    }
};
