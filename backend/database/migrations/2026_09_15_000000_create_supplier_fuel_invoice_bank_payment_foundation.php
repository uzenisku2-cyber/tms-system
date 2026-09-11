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
        Schema::create('supplier_fuel_invoice_bank_payments', static function (Blueprint $table): void {
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
            $table->foreignId('matched_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('matched_at');
            $table->foreignId('reversed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->unsignedInteger('revision');
            $table->timestampsTz();

            $table->unique(['owner_organization_id', 'idempotency_key'], 'supplier_fuel_bank_payment_idempotency_unique');
            $table->index(['billing_document_id', 'status'], 'supplier_fuel_bank_payment_document_status_index');
            $table->index(['bank_transaction_evidence_id', 'status'], 'supplier_fuel_bank_payment_evidence_status_index');
        });

        Schema::create('supplier_fuel_invoice_bank_payment_events', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('supplier_fuel_invoice_bank_payment_id')->constrained('supplier_fuel_invoice_bank_payments')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('event_type', 32);
            $table->uuid('idempotency_key')->nullable();
            $table->char('command_fingerprint', 64)->nullable();
            $table->text('reason');
            $table->json('evidence');
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('occurred_at');

            $table->unique(['supplier_fuel_invoice_bank_payment_id', 'revision'], 'supplier_fuel_bank_payment_event_revision_unique');
            $table->unique(['supplier_fuel_invoice_bank_payment_id', 'idempotency_key'], 'supplier_fuel_bank_payment_event_idempotency_unique');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE supplier_fuel_invoice_bank_payments ADD CONSTRAINT supplier_fuel_bank_payment_values_check CHECK (allocated_amount_minor > 0 AND currency ~ '^[A-Z]{3}$' AND status IN ('active','reversed') AND revision >= 1 AND ((status='active' AND reversed_by_user_id IS NULL AND reversed_at IS NULL AND reversal_reason IS NULL) OR (status='reversed' AND reversed_by_user_id IS NOT NULL AND reversed_at IS NOT NULL AND reversal_reason IS NOT NULL)))");
            DB::statement("ALTER TABLE supplier_fuel_invoice_bank_payment_events ADD CONSTRAINT supplier_fuel_bank_payment_event_values_check CHECK (revision >= 1 AND event_type IN ('payment_allocated','payment_reversed'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_fuel_invoice_bank_payment_events');
        Schema::dropIfExists('supplier_fuel_invoice_bank_payments');
    }
};
