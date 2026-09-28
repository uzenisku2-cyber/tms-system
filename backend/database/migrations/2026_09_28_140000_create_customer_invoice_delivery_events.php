<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_invoice_delivery_events', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('billing_document_id')->constrained('billing_documents')->restrictOnDelete();
            $table->foreignId('customer_invoice_pdf_artifact_id')->constrained('customer_invoice_pdf_artifacts')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->uuid('idempotency_key');
            $table->char('command_fingerprint', 64);
            $table->char('pdf_sha256', 64);
            $table->string('method', 32);
            $table->string('recipient', 255);
            $table->timestamp('delivered_at');
            $table->string('evidence_reference', 255);
            $table->text('reason');
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('recorded_at');
            $table->unique(['billing_document_id', 'revision'], 'invoice_delivery_revision_unique');
            $table->unique(['billing_document_id', 'idempotency_key'], 'invoice_delivery_key_unique');
            $table->index(['owner_organization_id', 'billing_document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_invoice_delivery_events');
    }
};
