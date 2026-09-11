<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class SupplierFuelInvoiceRebillingCoverageFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rebilling_coverage_tables_expose_exact_comparable_amounts_and_audit(): void
    {
        self::assertTrue(Schema::hasColumns('supplier_fuel_invoice_rebilling_coverages', [
            'public_id', 'owner_organization_id', 'billing_document_id', 'comparison_basis',
            'purchase_amount_minor', 'rebilled_amount_minor', 'unrebilled_amount_minor',
            'margin_minor', 'status', 'source_snapshot', 'revision', 'evaluated_at',
        ]));
        self::assertTrue(Schema::hasColumns('supplier_fuel_invoice_rebilling_coverage_lines', [
            'coverage_id', 'supplier_fuel_invoice_transaction_allocation_id', 'fuel_transaction_id',
            'fuel_transaction_settlement_application_id', 'financial_calculation_id',
            'financial_settlement_statement_id', 'output_billing_document_id', 'recipient_type',
            'comparison_basis', 'purchase_amount_minor', 'rebilled_amount_minor',
            'unrebilled_amount_minor', 'margin_minor', 'status', 'source_snapshot',
        ]));
        self::assertTrue(Schema::hasColumns('supplier_fuel_invoice_rebilling_coverage_events', [
            'coverage_id', 'revision', 'event_type', 'payload', 'actor_user_id', 'occurred_at',
        ]));
    }
}
