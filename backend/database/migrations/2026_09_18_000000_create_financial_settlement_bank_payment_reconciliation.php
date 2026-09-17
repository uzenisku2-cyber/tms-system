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
        Schema::create('financial_settlement_bank_payment_reconciliations', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('financial_settlement_bank_payment_id')->unique()->constrained('financial_settlement_bank_payments')->restrictOnDelete();
            $table->foreignId('financial_settlement_statement_id')->constrained('financial_settlement_statements')->restrictOnDelete();
            $table->foreignId('bank_transaction_evidence_id')->constrained('bank_transaction_evidence')->restrictOnDelete();
            $table->unsignedInteger('payment_revision');
            $table->string('status', 16);
            $table->unsignedInteger('revision');
            $table->text('reason');
            $table->foreignId('confirmed_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('confirmed_at');
            $table->foreignId('reopened_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('reopened_at')->nullable();
            $table->timestampsTz();
            $table->index(['owner_organization_id', 'status'], 'fs_bank_reconciliation_owner_status_index');
            $table->index(['bank_transaction_evidence_id', 'status'], 'fs_bank_reconciliation_evidence_status_index');
        });

        Schema::create('financial_settlement_bank_payment_reconciliation_events', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('financial_settlement_bank_payment_reconciliation_id')->constrained('financial_settlement_bank_payment_reconciliations', indexName: 'fs_bank_reconciliation_event_parent_fk')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('event_type', 40);
            $table->string('from_status', 16)->nullable();
            $table->string('to_status', 16);
            $table->uuid('idempotency_key');
            $table->char('command_fingerprint', 64);
            $table->text('reason');
            $table->json('evidence');
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('occurred_at');
            $table->unique(['financial_settlement_bank_payment_reconciliation_id', 'revision'], 'fs_bank_reconciliation_event_revision_unique');
            $table->unique(['financial_settlement_bank_payment_reconciliation_id', 'idempotency_key'], 'fs_bank_reconciliation_event_idempotency_unique');
            $table->unique(['owner_organization_id', 'idempotency_key'], 'fs_bank_reconciliation_event_owner_idempotency_unique');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE financial_settlement_bank_payment_reconciliations ADD CONSTRAINT fs_bank_reconciliation_values_check CHECK (payment_revision >= 1 AND revision >= 1 AND status IN ('confirmed','open') AND ((status='confirmed' AND reopened_by_user_id IS NULL AND reopened_at IS NULL) OR (status='open' AND reopened_by_user_id IS NOT NULL AND reopened_at IS NOT NULL)))");
            DB::statement("ALTER TABLE financial_settlement_bank_payment_reconciliation_events ADD CONSTRAINT fs_bank_reconciliation_event_values_check CHECK (revision >= 1 AND event_type IN ('reconciliation_confirmed','reconciliation_reopened') AND to_status IN ('confirmed','open') AND (from_status IS NULL OR from_status IN ('confirmed','open')))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_settlement_bank_payment_reconciliation_events');
        Schema::dropIfExists('financial_settlement_bank_payment_reconciliations');
    }
};
