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
        Schema::create('bank_statement_import_batches', function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('organization_context_id');
            $t->uuid('idempotency_key');
            $t->string('status', 32);
            $t->string('original_filename');
            $t->char('file_sha256', 64);
            $t->string('source_type', 32);
            $t->string('parser_version', 64);
            $t->string('mapping_version', 64);
            $t->string('delimiter', 4);
            $t->string('encoding', 32);
            $t->json('mapping');
            $t->unsignedInteger('source_row_count')->default(0);
            $t->unsignedInteger('accepted_row_count')->default(0);
            $t->unsignedInteger('duplicate_candidate_row_count')->default(0);
            $t->unsignedInteger('rejected_row_count')->default(0);
            $t->foreignId('imported_by_user_id');
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->foreign('organization_context_id', 'bsib_org_fk')->references('id')->on('organizations')->restrictOnDelete();
            $t->foreign('imported_by_user_id', 'bsib_user_fk')->references('id')->on('users')->restrictOnDelete();
            $t->unique(['organization_context_id', 'idempotency_key'], 'bsib_org_idempotency_unique');
            $t->unique(['organization_context_id', 'file_sha256'], 'bsib_org_file_unique');
        });
        Schema::create('bank_statement_import_rows', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('bank_statement_import_batch_id');
            $t->unsignedInteger('source_row');
            $t->string('status', 32);
            $t->char('row_fingerprint', 64);
            $t->char('transaction_fingerprint', 64)->nullable();
            $t->json('raw_payload');
            $t->json('normalized_payload')->nullable();
            $t->json('validation_messages')->nullable();
            $t->foreignId('bank_transaction_evidence_id')->nullable();
            $t->timestamps();
            $t->foreign('bank_statement_import_batch_id', 'bsir_batch_fk')->references('id')->on('bank_statement_import_batches')->restrictOnDelete();
            $t->foreign('bank_transaction_evidence_id', 'bsir_evidence_fk')->references('id')->on('bank_transaction_evidence')->restrictOnDelete();
            $t->unique(['bank_statement_import_batch_id', 'source_row'], 'bsir_batch_row_unique');
            $t->index(['bank_statement_import_batch_id', 'status'], 'bsir_batch_status_index');
        });
        Schema::create('bank_statement_import_duplicate_candidates', function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('bank_statement_import_row_id');
            $t->foreignId('candidate_bank_transaction_evidence_id');
            $t->string('comparison_method', 64);
            $t->json('matching_fields');
            $t->decimal('confidence', 5, 4);
            $t->string('decision', 32);
            $t->timestamp('detected_at');
            $t->timestamps();
            $t->foreign('bank_statement_import_row_id', 'bsidc_row_fk')->references('id')->on('bank_statement_import_rows')->restrictOnDelete();
            $t->foreign('candidate_bank_transaction_evidence_id', 'bsidc_evidence_fk')->references('id')->on('bank_transaction_evidence')->restrictOnDelete();
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE bank_statement_import_batches ADD CONSTRAINT bsib_values_check CHECK (status IN ('processing','completed','completed_with_review','failed') AND source_type = 'configurable_csv')");
            DB::statement("ALTER TABLE bank_statement_import_rows ADD CONSTRAINT bsir_values_check CHECK (status IN ('accepted','duplicate_candidate','rejected'))");
            DB::statement("ALTER TABLE bank_statement_import_duplicate_candidates ADD CONSTRAINT bsidc_values_check CHECK (comparison_method IN ('exact_fingerprint','probable_core_fields') AND decision = 'unresolved' AND confidence >= 0 AND confidence <= 1)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_import_duplicate_candidates');
        Schema::dropIfExists('bank_statement_import_rows');
        Schema::dropIfExists('bank_statement_import_batches');
    }
};
