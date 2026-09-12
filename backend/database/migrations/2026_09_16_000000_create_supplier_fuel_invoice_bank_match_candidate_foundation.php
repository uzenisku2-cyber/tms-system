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
        Schema::create('supplier_fuel_invoice_bank_match_candidates', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('billing_document_id')->constrained('billing_documents')->restrictOnDelete();
            $table->foreignId('bank_transaction_evidence_id')->constrained('bank_transaction_evidence')->restrictOnDelete();
            $table->unsignedInteger('bank_transaction_evidence_revision');
            $table->uuid('idempotency_key');
            $table->char('candidate_fingerprint', 64);
            $table->char('currency', 3);
            $table->unsignedBigInteger('bank_amount_minor');
            $table->unsignedBigInteger('invoice_unpaid_amount_minor');
            $table->unsignedBigInteger('proposed_amount_minor');
            $table->unsignedSmallInteger('score_basis_points');
            $table->string('status', 16);
            $table->json('match_reasons');
            $table->json('source_snapshot');
            $table->unsignedInteger('revision');
            $table->foreignId('proposed_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('proposed_at');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->text('review_reason')->nullable();
            $table->foreignId('supplier_fuel_invoice_bank_payment_id')->nullable()->unique()->constrained('supplier_fuel_invoice_bank_payments')->restrictOnDelete();
            $table->foreignId('materialized_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('materialized_at')->nullable();
            $table->timestampsTz();

            $table->unique(['owner_organization_id', 'idempotency_key'], 'supplier_fuel_match_candidate_idempotency_unique');
            $table->unique(['owner_organization_id', 'candidate_fingerprint'], 'supplier_fuel_match_candidate_fingerprint_unique');
            $table->index(['billing_document_id', 'status'], 'supplier_fuel_match_candidate_document_status_index');
            $table->index(['bank_transaction_evidence_id', 'status'], 'supplier_fuel_match_candidate_evidence_status_index');
        });

        Schema::create('supplier_fuel_invoice_bank_match_candidate_events', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('supplier_fuel_invoice_bank_match_candidate_id')->constrained('supplier_fuel_invoice_bank_match_candidates')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('event_type', 32);
            $table->uuid('idempotency_key')->nullable();
            $table->char('candidate_fingerprint', 64);
            $table->text('reason');
            $table->json('evidence');
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('occurred_at');

            $table->unique(['supplier_fuel_invoice_bank_match_candidate_id', 'revision'], 'supplier_fuel_match_event_revision_unique');
            $table->unique(['supplier_fuel_invoice_bank_match_candidate_id', 'idempotency_key'], 'supplier_fuel_match_event_idempotency_unique');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE supplier_fuel_invoice_bank_match_candidates ADD CONSTRAINT supplier_fuel_match_candidate_values_check CHECK (bank_amount_minor > 0 AND invoice_unpaid_amount_minor > 0 AND proposed_amount_minor > 0 AND proposed_amount_minor <= bank_amount_minor AND proposed_amount_minor <= invoice_unpaid_amount_minor AND score_basis_points <= 10000 AND currency ~ '^[A-Z]{3}$' AND status IN ('proposed','accepted','rejected','superseded') AND revision >= 1 AND ((status='proposed' AND reviewed_by_user_id IS NULL AND reviewed_at IS NULL AND review_reason IS NULL) OR (status<>'proposed' AND reviewed_by_user_id IS NOT NULL AND reviewed_at IS NOT NULL AND review_reason IS NOT NULL)))");
            DB::statement("ALTER TABLE supplier_fuel_invoice_bank_match_candidate_events ADD CONSTRAINT supplier_fuel_match_event_values_check CHECK (revision >= 1 AND event_type IN ('candidate_proposed','candidate_accepted','candidate_rejected','candidate_superseded'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_fuel_invoice_bank_match_candidate_events');
        Schema::dropIfExists('supplier_fuel_invoice_bank_match_candidates');
    }
};
