<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class SupplierFuelInvoiceRebillingCoverageContractTest extends TestCase
{
    public function test_foundation_tracks_the_complete_rebilling_chain_without_financial_side_effects(): void
    {
        $migration = file_get_contents(__DIR__.'/../../../../database/migrations/2026_09_15_120000_create_supplier_fuel_invoice_rebilling_coverage_foundation.php');
        self::assertIsString($migration);

        foreach (['supplier_fuel_invoice_transaction_allocation_id', 'fuel_transaction_settlement_application_id', 'financial_calculation_id', 'financial_settlement_statement_id', 'output_billing_document_id', "comparison_basis IN ('net','gross')", 'purchase_amount_minor', 'rebilled_amount_minor', 'unrebilled_amount_minor', 'margin_minor', "status IN ('complete','partial','missing','negative_margin')"] as $marker) {
            self::assertStringContainsString($marker, $migration);
        }

        self::assertStringContainsString('margin_minor = rebilled_amount_minor - purchase_amount_minor', $migration);
        self::assertStringNotContainsString('margin_minor >= 0', $migration);
        foreach (['BankTransactionEvidence::query()->create', 'BillingDocument::query()->create', 'FinancialSettlementStatement::query()->create'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $migration);
        }
    }
}
