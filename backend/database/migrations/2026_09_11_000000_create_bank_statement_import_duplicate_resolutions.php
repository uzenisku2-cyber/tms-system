<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statement_import_duplicate_resolutions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('organization_context_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('bank_statement_import_duplicate_candidate_id')->unique()->constrained('bank_statement_import_duplicate_candidates')->restrictOnDelete();
            $table->uuid('idempotency_key');
            $table->string('decision', 32);
            $table->text('reason');
            $table->foreignId('resolved_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('resolved_at');
            $table->timestampsTz();
            $table->unique(['organization_context_id', 'idempotency_key'], 'bank_duplicate_resolution_org_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_import_duplicate_resolutions');
    }
};
