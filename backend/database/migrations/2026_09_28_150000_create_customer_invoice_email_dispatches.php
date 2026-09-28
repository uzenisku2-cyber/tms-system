<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_invoice_email_dispatches', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('billing_document_id')->unique()->constrained('billing_documents')->restrictOnDelete();
            $table->foreignId('customer_invoice_pdf_artifact_id')->constrained('customer_invoice_pdf_artifacts')->restrictOnDelete();
            $table->char('pdf_sha256', 64);
            $table->uuid('idempotency_key');
            $table->char('command_fingerprint', 64);
            $table->string('recipient_email', 254);
            $table->text('reason');
            $table->string('status', 24);
            $table->foreignId('requested_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('queued_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('uncertain_at')->nullable();
            $table->string('failure_summary', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_invoice_email_dispatches');
    }
};
