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
        Schema::create('bank_transaction_evidence', function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('organization_context_id');
            $t->uuid('idempotency_key');
            $t->string('source_type', 32);
            $t->string('source_reference', 191);
            $t->string('bank_statement_reference', 191)->nullable();
            $t->string('direction', 16);
            $t->date('booked_at');
            $t->date('value_date')->nullable();
            $t->decimal('amount', 14, 2);
            $t->char('currency', 3);
            $t->string('account_identifier', 191)->nullable();
            $t->string('counterparty_name')->nullable();
            $t->string('counterparty_account_identifier', 191)->nullable();
            $t->string('variable_symbol', 32)->nullable();
            $t->text('message')->nullable();
            $t->text('evidence_note');
            $t->string('status', 24);
            $t->foreignId('recorded_by_user_id');
            $t->timestamp('recorded_at');
            $t->unsignedInteger('revision');
            $t->timestamps();
            $t->foreign('organization_context_id', 'bte_org_context_fk')->references('id')->on('organizations')->restrictOnDelete();
            $t->foreign('recorded_by_user_id', 'bte_recorded_by_fk')->references('id')->on('users')->restrictOnDelete();
            $t->unique(['organization_context_id', 'idempotency_key'], 'bte_org_idempotency_unique');
            $t->unique(['organization_context_id', 'source_type', 'source_reference'], 'bte_org_source_reference_unique');
            $t->index(['organization_context_id', 'booked_at', 'status'], 'bte_operational_index');
        });

        Schema::create('bank_transaction_evidence_events', function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('bank_transaction_evidence_id');
            $t->string('event_type', 64);
            $t->json('evidence');
            $t->foreignId('actor_user_id');
            $t->unsignedInteger('revision');
            $t->timestamp('occurred_at');
            $t->timestamps();
            $t->foreign('bank_transaction_evidence_id', 'btee_evidence_fk')->references('id')->on('bank_transaction_evidence')->restrictOnDelete();
            $t->foreign('actor_user_id', 'btee_actor_fk')->references('id')->on('users')->restrictOnDelete();
            $t->unique(['bank_transaction_evidence_id', 'revision'], 'btee_evidence_revision_unique');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE bank_transaction_evidence ADD CONSTRAINT bte_values_check CHECK (source_type IN ('manual_evidence','bank_import') AND direction IN ('credit','debit') AND status = 'recorded' AND amount > 0 AND revision >= 1)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transaction_evidence_events');
        Schema::dropIfExists('bank_transaction_evidence');
    }
};
