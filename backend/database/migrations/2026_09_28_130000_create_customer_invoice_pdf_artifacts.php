<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_invoice_pdf_artifacts', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('billing_document_id')->unique()->constrained('billing_documents')->restrictOnDelete();
            $table->unsignedBigInteger('commercial_identity_id');
            $table->foreign('commercial_identity_id', 'customer_invoice_pdf_identity_fk')
                ->references('id')->on('billing_document_commercial_identities')->restrictOnDelete();
            $table->char('snapshot_sha256', 64);
            $table->char('pdf_sha256', 64);
            $table->string('storage_path', 255);
            $table->unsignedBigInteger('byte_size');
            $table->timestamp('generated_at');
            $table->foreignId('generated_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_invoice_pdf_artifacts');
    }
};
