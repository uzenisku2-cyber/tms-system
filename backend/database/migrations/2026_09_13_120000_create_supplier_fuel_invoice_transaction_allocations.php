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
        Schema::create('supplier_fuel_invoice_transaction_allocations', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('billing_document_id')->constrained('billing_documents')->restrictOnDelete();
            $table->foreignId('fuel_transaction_id')->constrained('fuel_transactions')->restrictOnDelete();
            $table->uuid('idempotency_key');
            $table->char('command_fingerprint', 64);
            $table->unsignedBigInteger('allocated_amount_minor');
            $table->char('currency', 3);
            $table->string('status', 16);
            $table->unsignedInteger('revision')->default(1);
            $table->text('reason');
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reversed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
            $table->unique(['owner_organization_id', 'idempotency_key'], 'supplier_fuel_allocation_org_idempotency_unique');
            $table->index(['billing_document_id', 'status'], 'supplier_fuel_allocation_document_status_index');
            $table->index(['fuel_transaction_id', 'status'], 'supplier_fuel_allocation_transaction_status_index');
        });

        Schema::create('supplier_fuel_invoice_transaction_allocation_events', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('allocation_id')->constrained('supplier_fuel_invoice_transaction_allocations')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('event_type', 24);
            $table->text('reason');
            $table->json('evidence');
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('occurred_at');
            $table->unique(['allocation_id', 'revision'], 'supplier_fuel_allocation_event_revision_unique');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE supplier_fuel_invoice_transaction_allocations ADD CONSTRAINT supplier_fuel_allocation_values_check CHECK (allocated_amount_minor > 0 AND currency ~ '^[A-Z]{3}$' AND status IN ('active','reversed') AND revision >= 1)");
            DB::statement("ALTER TABLE supplier_fuel_invoice_transaction_allocation_events ADD CONSTRAINT supplier_fuel_allocation_event_values_check CHECK (event_type IN ('allocated','reversed') AND revision >= 1)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_fuel_invoice_transaction_allocation_events');
        Schema::dropIfExists('supplier_fuel_invoice_transaction_allocations');
    }
};
