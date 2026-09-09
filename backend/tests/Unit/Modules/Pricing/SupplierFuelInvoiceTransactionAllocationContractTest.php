<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class SupplierFuelInvoiceTransactionAllocationContractTest extends TestCase
{
    public function test_allocation_is_exact_audited_and_non_executing(): void
    {
        $root = dirname(__DIR__, 4);
        $service = file_get_contents($root.'/app/Modules/Pricing/Services/SupplierFuelInvoiceTransactionAllocationService.php');
        $migration = file_get_contents($root.'/database/migrations/2026_09_13_120000_create_supplier_fuel_invoice_transaction_allocations.php');

        self::assertIsString($service);
        self::assertIsString($migration);
        self::assertStringContainsString('allocated_amount_minor', $service);
        self::assertStringContainsString('invoice_allocation_state', $service);
        self::assertStringContainsString('Allocation exceeds the remaining supplier invoice amount.', $service);
        self::assertStringContainsString('Allocation exceeds the remaining fuel transaction amount.', $service);
        self::assertStringContainsString("'bank_matching_performed' => false", $service);
        self::assertStringContainsString("'payment_marked' => false", $service);
        self::assertStringContainsString("'fuel_settlement_mutated' => false", $service);
        self::assertStringNotContainsString('BankTransactionEvidence::', $service);
        self::assertStringNotContainsString('FuelTransactionSettlementApplication::', $service);
        self::assertStringContainsString('supplier_fuel_allocation_org_idempotency_unique', $migration);
    }
}
