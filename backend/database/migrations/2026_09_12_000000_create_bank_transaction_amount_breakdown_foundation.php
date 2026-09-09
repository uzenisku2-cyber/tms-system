<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transaction_amount_breakdowns', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->uuid('breakdown_uid');
            $table->foreignId('organization_context_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('bank_transaction_evidence_id')->constrained('bank_transaction_evidence')->restrictOnDelete();
            $table->uuid('idempotency_key');
            $table->unsignedInteger('revision');
            $table->string('status', 32);
            $table->bigInteger('source_amount_minor');
            $table->bigInteger('allocated_amount_minor');
            $table->char('currency', 3);
            $table->text('reason');
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('recorded_at');
            $table->timestampsTz();
            $table->unique(['organization_context_id', 'idempotency_key'], 'bank_amount_breakdown_idempotency_unique');
            $table->unique(['breakdown_uid', 'revision'], 'bank_amount_breakdown_revision_unique');
            $table->unique(['bank_transaction_evidence_id', 'revision'], 'bank_amount_breakdown_evidence_revision_unique');
            $table->index(['organization_context_id', 'bank_transaction_evidence_id'], 'bank_amount_breakdown_scope_index');
        });

        Schema::create('bank_transaction_amount_breakdown_components', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('bank_transaction_amount_breakdown_id')->constrained('bank_transaction_amount_breakdowns')->cascadeOnDelete();
            $table->unsignedInteger('sequence_number');
            $table->string('component_type', 32);
            $table->string('label')->nullable();
            $table->bigInteger('amount_minor');
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->unique(['bank_transaction_amount_breakdown_id', 'sequence_number'], 'bank_amount_component_sequence_unique');
        });

        Schema::create('bank_transaction_amount_breakdown_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('bank_transaction_amount_breakdown_id')->constrained('bank_transaction_amount_breakdowns')->cascadeOnDelete();
            $table->string('event_type', 64);
            $table->json('evidence');
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transaction_amount_breakdown_events');
        Schema::dropIfExists('bank_transaction_amount_breakdown_components');
        Schema::dropIfExists('bank_transaction_amount_breakdowns');
    }
};
