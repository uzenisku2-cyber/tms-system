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
        Schema::create('financial_settlement_bank_match_candidates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('financial_settlement_statement_id')->constrained('financial_settlement_statements')->restrictOnDelete();
            $table->foreignId('billing_document_id')->constrained('billing_documents')->restrictOnDelete();
            $table->foreignId('bank_transaction_evidence_id')->constrained('bank_transaction_evidence')->restrictOnDelete();
            $table->unsignedInteger('bank_transaction_evidence_revision');
            $table->uuid('idempotency_key');
            $table->char('candidate_fingerprint', 64);
            $table->char('currency', 3);
            $table->string('expected_bank_direction', 16);
            $table->unsignedBigInteger('bank_amount_minor');
            $table->unsignedBigInteger('settlement_outstanding_amount_minor');
            $table->unsignedBigInteger('proposed_amount_minor');
            $table->unsignedSmallInteger('score_basis_points');
            $table->string('status', 24)->default('proposed');
            $table->json('match_reasons');
            $table->json('source_snapshot');
            $table->unsignedInteger('revision')->default(1);
            $table->foreignId('proposed_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('proposed_at');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_reason', 1000)->nullable();
            $table->timestamps();

            $table->unique(['owner_organization_id', 'idempotency_key'], 'fs_bank_candidate_owner_idempotency_unique');
            $table->unique(['owner_organization_id', 'candidate_fingerprint'], 'fs_bank_candidate_owner_fingerprint_unique');
            $table->index(['owner_organization_id', 'financial_settlement_statement_id', 'status'], 'fs_bank_candidate_statement_status_index');
            $table->index(['owner_organization_id', 'bank_transaction_evidence_id', 'status'], 'fs_bank_candidate_evidence_status_index');
        });

        Schema::create('financial_settlement_bank_match_candidate_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('financial_settlement_bank_match_candidate_id')->constrained('financial_settlement_bank_match_candidates')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('event_type', 40);
            $table->uuid('idempotency_key');
            $table->char('candidate_fingerprint', 64);
            $table->string('reason', 1000);
            $table->json('evidence');
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('occurred_at');

            $table->unique(['financial_settlement_bank_match_candidate_id', 'revision'], 'fs_bank_candidate_event_revision_unique');
            $table->unique(['financial_settlement_bank_match_candidate_id', 'idempotency_key'], 'fs_bank_candidate_event_idempotency_unique');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE financial_settlement_bank_match_candidates ADD CONSTRAINT fs_bank_candidate_values_check CHECK (bank_transaction_evidence_revision >= 1 AND currency ~ '^[A-Z]{3}$' AND expected_bank_direction IN ('debit','credit') AND bank_amount_minor > 0 AND settlement_outstanding_amount_minor > 0 AND proposed_amount_minor > 0 AND proposed_amount_minor <= bank_amount_minor AND proposed_amount_minor <= settlement_outstanding_amount_minor AND score_basis_points BETWEEN 1 AND 10000 AND status IN ('proposed','accepted','rejected','superseded') AND revision >= 1)");
            DB::statement("ALTER TABLE financial_settlement_bank_match_candidates ADD CONSTRAINT fs_bank_candidate_review_check CHECK ((status = 'proposed' AND reviewed_by_user_id IS NULL AND reviewed_at IS NULL AND review_reason IS NULL) OR (status IN ('accepted','rejected','superseded') AND reviewed_by_user_id IS NOT NULL AND reviewed_at IS NOT NULL AND review_reason IS NOT NULL))");
            DB::statement("ALTER TABLE financial_settlement_bank_match_candidate_events ADD CONSTRAINT fs_bank_candidate_event_values_check CHECK (revision >= 1 AND event_type IN ('candidate_proposed','candidate_accepted','candidate_rejected','candidate_superseded'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_settlement_bank_match_candidate_events');
        Schema::dropIfExists('financial_settlement_bank_match_candidates');
    }
};
