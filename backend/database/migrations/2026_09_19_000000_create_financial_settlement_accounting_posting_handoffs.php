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
        Schema::create('financial_settlement_accounting_posting_handoffs', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('financial_settlement_bank_payment_reconciliation_id')->constrained('financial_settlement_bank_payment_reconciliations', indexName: 'fsaph_reconciliation_fk')->restrictOnDelete();
            $table->foreignId('financial_settlement_bank_payment_id')->constrained('financial_settlement_bank_payments', indexName: 'fsaph_payment_fk')->restrictOnDelete();
            $table->foreignId('financial_settlement_statement_id')->constrained('financial_settlement_statements', indexName: 'fsaph_statement_fk')->restrictOnDelete();
            $table->foreignId('billing_document_id')->nullable()->constrained('billing_documents', indexName: 'fsaph_document_fk')->restrictOnDelete();
            $table->foreignId('bank_transaction_evidence_id')->constrained('bank_transaction_evidence', indexName: 'fsaph_bank_evidence_fk')->restrictOnDelete();
            $table->uuid('idempotency_key');
            $table->string('command_fingerprint', 64);
            $table->unsignedInteger('reconciliation_revision');
            $table->unsignedInteger('payment_revision');
            $table->unsignedInteger('statement_revision');
            $table->unsignedInteger('bank_transaction_evidence_revision');
            $table->date('posting_date');
            $table->string('accounting_reference')->nullable();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('direction', 32);
            $table->string('status', 32);
            $table->text('reason');
            $table->json('source_snapshot');
            $table->foreignId('prepared_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('prepared_at');
            $table->unsignedInteger('revision');
            $table->unique(['owner_organization_id', 'idempotency_key'], 'fsaph_org_idem_unique');
            $table->unique(['financial_settlement_bank_payment_reconciliation_id', 'reconciliation_revision'], 'fsaph_reconciliation_revision_unique');
            $table->index(['owner_organization_id', 'posting_date'], 'fsaph_org_posting_date_index');
        });

        Schema::create('financial_settlement_accounting_posting_handoff_events', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('financial_settlement_accounting_posting_handoff_id')->constrained('financial_settlement_accounting_posting_handoffs', indexName: 'fsaphe_handoff_fk')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('event_type', 64);
            $table->uuid('idempotency_key');
            $table->string('command_fingerprint', 64);
            $table->json('evidence');
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('occurred_at');
            $table->unique(['financial_settlement_accounting_posting_handoff_id', 'revision'], 'fsaphe_handoff_revision_unique');
            $table->index(['owner_organization_id', 'occurred_at'], 'fsaphe_org_occurred_index');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE financial_settlement_accounting_posting_handoffs ADD CONSTRAINT fsaph_status_check CHECK (status = 'prepared')");
            DB::statement('ALTER TABLE financial_settlement_accounting_posting_handoffs ADD CONSTRAINT fsaph_amount_check CHECK (amount_minor > 0)');
            DB::statement('ALTER TABLE financial_settlement_accounting_posting_handoffs ADD CONSTRAINT fsaph_revision_check CHECK (revision > 0 AND reconciliation_revision > 0 AND payment_revision > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_settlement_accounting_posting_handoff_events');
        Schema::dropIfExists('financial_settlement_accounting_posting_handoffs');
    }
};
