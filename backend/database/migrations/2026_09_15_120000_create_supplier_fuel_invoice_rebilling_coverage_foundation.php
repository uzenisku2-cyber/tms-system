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
        Schema::create('supplier_fuel_invoice_rebilling_coverages', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('billing_document_id')->constrained('billing_documents')->restrictOnDelete();
            $table->uuid('idempotency_key');
            $table->char('command_fingerprint', 64);
            $table->string('comparison_basis', 16);
            $table->char('currency', 3);
            $table->unsignedBigInteger('purchase_amount_minor');
            $table->unsignedBigInteger('rebilled_amount_minor');
            $table->unsignedBigInteger('unrebilled_amount_minor');
            $table->bigInteger('margin_minor');
            $table->string('status', 32);
            $table->json('source_snapshot');
            $table->unsignedInteger('revision')->default(1);
            $table->foreignId('evaluated_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('evaluated_at');
            $table->timestampsTz();
            $table->unique(['owner_organization_id', 'idempotency_key'], 'supplier_fuel_rebilling_org_idempotency_unique');
            $table->index(['billing_document_id', 'status'], 'supplier_fuel_rebilling_document_status_index');
        });

        Schema::create('supplier_fuel_invoice_rebilling_coverage_lines', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('coverage_id')->constrained('supplier_fuel_invoice_rebilling_coverages')->restrictOnDelete();
            $table->foreignId('supplier_fuel_invoice_transaction_allocation_id')->constrained('supplier_fuel_invoice_transaction_allocations')->restrictOnDelete();
            $table->foreignId('fuel_transaction_id')->constrained('fuel_transactions')->restrictOnDelete();
            $table->foreignId('fuel_transaction_settlement_application_id')->nullable()->constrained('fuel_transaction_settlement_applications')->restrictOnDelete();
            $table->foreignId('financial_calculation_id')->nullable()->constrained('financial_calculations')->restrictOnDelete();
            $table->foreignId('financial_settlement_statement_id')->nullable()->constrained('financial_settlement_statements')->restrictOnDelete();
            $table->foreignId('output_billing_document_id')->nullable()->constrained('billing_documents')->restrictOnDelete();
            $table->string('recipient_type', 16)->nullable();
            $table->foreignId('recipient_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $table->foreignId('recipient_driver_id')->nullable()->constrained('drivers')->restrictOnDelete();
            $table->string('comparison_basis', 16);
            $table->unsignedBigInteger('purchase_amount_minor');
            $table->unsignedBigInteger('rebilled_amount_minor');
            $table->unsignedBigInteger('unrebilled_amount_minor');
            $table->bigInteger('margin_minor');
            $table->char('currency', 3);
            $table->string('status', 32);
            $table->json('source_snapshot');
            $table->unsignedInteger('position');
            $table->timestampTz('created_at');
            $table->unique(['coverage_id', 'supplier_fuel_invoice_transaction_allocation_id'], 'supplier_fuel_rebilling_allocation_unique');
            $table->index(['fuel_transaction_id', 'status'], 'supplier_fuel_rebilling_transaction_status_index');
            $table->index(['recipient_type', 'status'], 'supplier_fuel_rebilling_recipient_status_index');
        });

        Schema::create('supplier_fuel_invoice_rebilling_coverage_events', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('coverage_id')->constrained('supplier_fuel_invoice_rebilling_coverages')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('event_type', 32);
            $table->json('payload');
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('occurred_at');
            $table->unique(['coverage_id', 'revision'], 'supplier_fuel_rebilling_event_revision_unique');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE supplier_fuel_invoice_rebilling_coverages ADD CONSTRAINT supplier_fuel_rebilling_values_check CHECK (comparison_basis IN ('net','gross') AND currency ~ '^[A-Z]{3}$' AND unrebilled_amount_minor = GREATEST(purchase_amount_minor - rebilled_amount_minor, 0) AND margin_minor = rebilled_amount_minor - purchase_amount_minor AND status IN ('complete','incomplete','negative_margin') AND revision >= 1)");
            DB::statement("ALTER TABLE supplier_fuel_invoice_rebilling_coverage_lines ADD CONSTRAINT supplier_fuel_rebilling_line_values_check CHECK ((recipient_type IS NULL OR recipient_type IN ('driver','carrier')) AND comparison_basis IN ('net','gross') AND currency ~ '^[A-Z]{3}$' AND unrebilled_amount_minor = GREATEST(purchase_amount_minor - rebilled_amount_minor, 0) AND margin_minor = rebilled_amount_minor - purchase_amount_minor AND status IN ('complete','partial','missing','negative_margin') AND position >= 1)");
            DB::statement("ALTER TABLE supplier_fuel_invoice_rebilling_coverage_events ADD CONSTRAINT supplier_fuel_rebilling_event_values_check CHECK (event_type = 'evaluated' AND revision >= 1)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_fuel_invoice_rebilling_coverage_events');
        Schema::dropIfExists('supplier_fuel_invoice_rebilling_coverage_lines');
        Schema::dropIfExists('supplier_fuel_invoice_rebilling_coverages');
    }
};
