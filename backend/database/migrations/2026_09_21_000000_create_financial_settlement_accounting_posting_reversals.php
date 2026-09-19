<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_settlement_accounting_posting_reversals', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations');
            $table->foreignId('financial_settlement_accounting_posting_execution_id')->constrained('financial_settlement_accounting_posting_executions');
            $table->uuid('idempotency_key');
            $table->string('command_fingerprint', 64);
            $table->char('currency', 3);
            $table->unsignedBigInteger('total_debit_minor');
            $table->unsignedBigInteger('total_credit_minor');
            $table->string('status', 32);
            $table->text('reason');
            $table->json('source_snapshot');
            $table->unsignedInteger('revision');
            $table->foreignId('reversed_by_user_id')->constrained('users');
            $table->timestampTz('reversed_at');
            $table->timestampsTz();
            $table->unique(['owner_organization_id', 'idempotency_key'], 'fs_posting_reversal_owner_idempotency_unique');
            $table->unique(['owner_organization_id', 'financial_settlement_accounting_posting_execution_id'], 'fs_posting_reversal_execution_unique');
        });
        Schema::create('financial_settlement_accounting_posting_reversal_entries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations');
            $table->foreignId('financial_settlement_accounting_posting_reversal_id')->constrained('financial_settlement_accounting_posting_reversals');
            $table->foreignId('original_posting_entry_id')->constrained('financial_settlement_accounting_posting_entries');
            $table->unsignedSmallInteger('sequence');
            $table->string('entry_side', 16);
            $table->string('account_code', 64);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->json('source_snapshot');
            $table->timestampsTz();
            $table->unique(['financial_settlement_accounting_posting_reversal_id', 'sequence'], 'fs_posting_reversal_entry_sequence_unique');
            $table->unique(['financial_settlement_accounting_posting_reversal_id', 'original_posting_entry_id'], 'fs_posting_reversal_original_entry_unique');
        });
        Schema::create('financial_settlement_accounting_posting_reversal_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations');
            $table->foreignId('financial_settlement_accounting_posting_reversal_id')->constrained('financial_settlement_accounting_posting_reversals');
            $table->string('event_type', 64);
            $table->unsignedInteger('revision');
            $table->json('payload');
            $table->foreignId('actor_user_id')->constrained('users');
            $table->timestampTz('occurred_at');
            $table->timestampsTz();
            $table->unique(['financial_settlement_accounting_posting_reversal_id', 'revision'], 'fs_posting_reversal_event_revision_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_settlement_accounting_posting_reversal_events');
        Schema::dropIfExists('financial_settlement_accounting_posting_reversal_entries');
        Schema::dropIfExists('financial_settlement_accounting_posting_reversals');
    }
};
