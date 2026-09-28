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
        Schema::create('customer_invoice_bank_payments', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('billing_document_id')->constrained('billing_documents')->restrictOnDelete();
            $table->foreignId('bank_transaction_evidence_id')->constrained('bank_transaction_evidence')->restrictOnDelete();
            $table->unsignedInteger('bank_transaction_evidence_revision');
            $table->uuid('idempotency_key');
            $table->char('command_fingerprint', 64);
            $table->unsignedBigInteger('allocated_amount_minor');
            $table->char('currency', 3);
            $table->string('status', 16);
            $table->text('reason');
            $table->foreignId('allocated_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('allocated_at');
            $table->foreignId('reversed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->unsignedInteger('revision');
            $table->timestamps();
            $table->unique(['owner_organization_id', 'idempotency_key'], 'customer_invoice_payment_key_unique');
            $table->index(['billing_document_id', 'status'], 'customer_invoice_payment_document_status');
            $table->index(['bank_transaction_evidence_id', 'status'], 'customer_invoice_payment_bank_status');
        });

        Schema::create('customer_invoice_bank_payment_events', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('customer_invoice_bank_payment_id')->constrained('customer_invoice_bank_payments')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('event_type', 32);
            $table->uuid('idempotency_key');
            $table->char('command_fingerprint', 64);
            $table->text('reason');
            $table->json('evidence');
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('occurred_at');
            $table->unique(['customer_invoice_bank_payment_id', 'revision'], 'customer_invoice_payment_event_revision');
            $table->unique(['customer_invoice_bank_payment_id', 'idempotency_key'], 'customer_invoice_payment_event_key');
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE customer_invoice_bank_payments ADD CONSTRAINT customer_invoice_payment_values_check CHECK (bank_transaction_evidence_revision >= 1 AND allocated_amount_minor > 0 AND currency ~ '^[A-Z]{3}$' AND status IN ('active','reversed') AND revision >= 1)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_invoice_bank_payment_events');
        Schema::dropIfExists('customer_invoice_bank_payments');
    }
};
