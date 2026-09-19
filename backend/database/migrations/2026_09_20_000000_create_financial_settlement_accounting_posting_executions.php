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
        Schema::create('financial_settlement_accounting_posting_executions', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('financial_settlement_accounting_posting_handoff_id')->constrained('financial_settlement_accounting_posting_handoffs', indexName: 'fsape_handoff_fk')->restrictOnDelete();
            $table->uuid('idempotency_key');
            $table->string('command_fingerprint', 64);
            $table->unsignedInteger('handoff_revision');
            $table->date('posting_date');
            $table->string('accounting_reference', 191)->nullable();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('direction', 24);
            $table->string('status', 24);
            $table->text('description');
            $table->json('source_snapshot');
            $table->foreignId('executed_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('executed_at');
            $table->unsignedInteger('revision');
            $table->unique(['owner_organization_id', 'idempotency_key'], 'fsape_org_idem_unique');
            $table->unique('financial_settlement_accounting_posting_handoff_id', 'fsape_handoff_unique');
            $table->index(['owner_organization_id', 'posting_date'], 'fsape_org_posting_date_index');
        });
        Schema::create('financial_settlement_accounting_posting_entries', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('financial_settlement_accounting_posting_execution_id')->constrained('financial_settlement_accounting_posting_executions', indexName: 'fsapentry_execution_fk')->restrictOnDelete();
            $table->unsignedSmallInteger('sequence_number');
            $table->string('side', 8);
            $table->string('account_code', 64);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->text('description');
            $table->timestamp('occurred_at');
            $table->unique(['financial_settlement_accounting_posting_execution_id', 'sequence_number'], 'fsapentry_execution_sequence_unique');
        });
        Schema::create('financial_settlement_accounting_posting_execution_events', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('financial_settlement_accounting_posting_execution_id')->constrained('financial_settlement_accounting_posting_executions', indexName: 'fsapee_execution_fk')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('event_type', 64);
            $table->uuid('idempotency_key');
            $table->string('command_fingerprint', 64);
            $table->json('evidence');
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('occurred_at');
            $table->unique(['financial_settlement_accounting_posting_execution_id', 'revision'], 'fsapee_execution_revision_unique');
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE financial_settlement_accounting_posting_executions ADD CONSTRAINT fsape_status_check CHECK (status = 'posted' AND amount_minor > 0 AND revision > 0 AND handoff_revision > 0)");
            DB::statement("ALTER TABLE financial_settlement_accounting_posting_entries ADD CONSTRAINT fsapentry_values_check CHECK (side IN ('debit','credit') AND amount_minor > 0 AND sequence_number IN (1,2))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_settlement_accounting_posting_execution_events');
        Schema::dropIfExists('financial_settlement_accounting_posting_entries');
        Schema::dropIfExists('financial_settlement_accounting_posting_executions');
    }
};
