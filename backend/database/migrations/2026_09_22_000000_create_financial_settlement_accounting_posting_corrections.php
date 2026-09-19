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
        Schema::table('financial_settlement_accounting_posting_executions', function (Blueprint $table): void {
            $table->unsignedBigInteger('financial_settlement_accounting_posting_handoff_id')->nullable()->change();
        });

        Schema::create('financial_settlement_accounting_posting_corrections', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations');
            $table->foreignId('original_execution_id')->constrained('financial_settlement_accounting_posting_executions');
            $table->foreignId('reversal_id')->constrained('financial_settlement_accounting_posting_reversals');
            $table->foreignId('replacement_execution_id')->constrained('financial_settlement_accounting_posting_executions');
            $table->uuid('idempotency_key');
            $table->string('command_fingerprint', 64);
            $table->text('reason');
            $table->json('source_snapshot');
            $table->foreignId('corrected_by_user_id')->constrained('users');
            $table->timestampTz('corrected_at');
            $table->unsignedInteger('revision')->default(1);
            $table->unique(['owner_organization_id', 'idempotency_key'], 'fsap_corrections_owner_idempotency_unique');
            $table->unique(['owner_organization_id', 'original_execution_id'], 'fsap_corrections_owner_original_unique');
            $table->unique(['owner_organization_id', 'reversal_id'], 'fsap_corrections_owner_reversal_unique');
            $table->unique(['owner_organization_id', 'replacement_execution_id'], 'fsap_corrections_owner_replacement_unique');
        });

        Schema::create('financial_settlement_accounting_posting_correction_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('correction_id')->constrained('financial_settlement_accounting_posting_corrections');
            $table->string('event_type', 100);
            $table->json('payload');
            $table->foreignId('actor_user_id')->constrained('users');
            $table->timestampTz('occurred_at');
            $table->unsignedInteger('revision');
            $table->unique(['correction_id', 'revision'], 'fsap_correction_events_revision_unique');
        });
    }

    public function down(): void
    {
        $replacementExecutionIds = DB::table('financial_settlement_accounting_posting_corrections')
            ->pluck('replacement_execution_id');

        DB::table('financial_settlement_accounting_posting_correction_events')->delete();
        DB::table('financial_settlement_accounting_posting_corrections')->delete();
        DB::table('financial_settlement_accounting_posting_entries')
            ->whereIn('financial_settlement_accounting_posting_execution_id', $replacementExecutionIds)
            ->delete();
        DB::table('financial_settlement_accounting_posting_execution_events')
            ->whereIn('financial_settlement_accounting_posting_execution_id', $replacementExecutionIds)
            ->delete();
        DB::table('financial_settlement_accounting_posting_executions')
            ->whereIn('id', $replacementExecutionIds)
            ->delete();

        Schema::dropIfExists('financial_settlement_accounting_posting_correction_events');
        Schema::dropIfExists('financial_settlement_accounting_posting_corrections');
        Schema::table('financial_settlement_accounting_posting_executions', function (Blueprint $table): void {
            $table->unsignedBigInteger('financial_settlement_accounting_posting_handoff_id')->nullable(false)->change();
        });
    }
};
