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
        Schema::create('financial_settlement_statements', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations');
            $table->string('recipient_type', 32);
            $table->foreignId('recipient_organization_id')->nullable()->constrained('organizations');
            $table->foreignId('recipient_driver_id')->nullable()->constrained('drivers');
            $table->date('period_from');
            $table->date('period_until');
            $table->char('currency', 3);
            $table->string('status', 24)->default('draft');
            $table->unsignedBigInteger('earning_amount_minor')->default(0);
            $table->unsignedBigInteger('deduction_amount_minor')->default(0);
            $table->bigInteger('net_balance_minor')->default(0);
            $table->json('source_snapshot');
            $table->uuid('idempotency_key');
            $table->char('command_fingerprint', 64);
            $table->unsignedInteger('revision')->default(1);
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('billing_document_id')->nullable()->unique()->constrained('billing_documents')->restrictOnDelete();
            $table->string('output_kind', 32)->nullable();
            $table->string('output_direction', 16)->nullable();
            $table->timestamp('output_materialized_at')->nullable();
            $table->timestamps();

            $table->unique(['owner_organization_id', 'idempotency_key'], 'financial_settlement_statements_owner_idempotency_unique');
            $table->index(['owner_organization_id', 'status', 'period_from'], 'financial_settlement_statements_owner_status_period_index');
            $table->index(['recipient_organization_id', 'period_from'], 'financial_settlement_statements_recipient_org_period_index');
            $table->index(['recipient_driver_id', 'period_from'], 'financial_settlement_statements_recipient_driver_period_index');
            $table->index(['owner_organization_id', 'output_kind'], 'financial_settlement_statements_owner_output_index');
        });

        Schema::create('financial_settlement_statement_lines', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('financial_settlement_statement_id')->constrained('financial_settlement_statements');
            $table->unsignedInteger('position');
            $table->string('source_type', 48);
            $table->uuid('source_public_id');
            $table->unsignedInteger('source_revision');
            $table->foreignId('financial_calculation_id')->nullable()->constrained('financial_calculations');
            $table->foreignId('financial_mutual_charge_id')->nullable()->constrained('financial_mutual_charges');
            $table->string('effect', 16);
            $table->string('description', 500);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->json('source_snapshot');
            $table->timestamp('created_at');

            $table->unique(['financial_settlement_statement_id', 'position'], 'financial_settlement_statement_lines_position_unique');
            $table->unique(['financial_settlement_statement_id', 'source_type', 'source_public_id'], 'financial_settlement_statement_lines_source_unique');
        });

        Schema::create('financial_settlement_statement_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('financial_settlement_statement_id')->constrained('financial_settlement_statements');
            $table->unsignedInteger('revision');
            $table->string('event_type', 32);
            $table->uuid('idempotency_key');
            $table->char('command_fingerprint', 64);
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->string('reason', 1000);
            $table->json('evidence');
            $table->foreignId('actor_user_id')->constrained('users');
            $table->timestamp('occurred_at');

            $table->unique(['financial_settlement_statement_id', 'revision'], 'financial_settlement_statement_events_revision_unique');
            $table->unique(['financial_settlement_statement_id', 'idempotency_key'], 'financial_settlement_statement_events_idempotency_unique');
        });

        if (DB::getDriverName() !== 'sqlite') {
            $checks = [
                'financial_settlement_statements_period_check' => 'period_until >= period_from',
                'financial_settlement_statements_net_check' => 'net_balance_minor = earning_amount_minor - deduction_amount_minor',
                'financial_settlement_statements_org_recipient_check' => "recipient_type <> 'organization' OR (recipient_organization_id IS NOT NULL AND recipient_driver_id IS NULL)",
                'financial_settlement_statements_driver_recipient_check' => "recipient_type <> 'driver' OR (recipient_driver_id IS NOT NULL AND recipient_organization_id IS NULL)",
                'financial_settlement_statements_output_consistency_check' => "(output_kind IS NULL AND output_direction IS NULL AND output_materialized_at IS NULL AND billing_document_id IS NULL) OR (output_kind = 'zero_balance' AND output_direction = 'none' AND output_materialized_at IS NOT NULL AND billing_document_id IS NULL) OR (output_kind IN ('carrier_payable','carrier_receivable','driver_payout','driver_deduction') AND output_direction IN ('payable','receivable','internal') AND output_materialized_at IS NOT NULL AND billing_document_id IS NOT NULL)",
            ];
            foreach ($checks as $name => $expression) {
                DB::statement("ALTER TABLE financial_settlement_statements ADD CONSTRAINT {$name} CHECK ({$expression})");
            }
            DB::statement('ALTER TABLE financial_settlement_statement_lines ADD CONSTRAINT financial_settlement_statement_lines_amount_check CHECK (amount_minor > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_settlement_statement_events');
        Schema::dropIfExists('financial_settlement_statement_lines');
        Schema::dropIfExists('financial_settlement_statements');
    }
};
