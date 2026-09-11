<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class SupplierFuelInvoiceBankPaymentContractTest extends TestCase
{
    public function test_foundation_keeps_payment_allocation_separate_and_exact(): void
    {
        $model = file_get_contents(__DIR__.'/../../../../app/Modules/Pricing/Models/SupplierFuelInvoiceBankPayment.php');
        $event = file_get_contents(__DIR__.'/../../../../app/Modules/Pricing/Models/SupplierFuelInvoiceBankPaymentEvent.php');
        $migration = file_get_contents(__DIR__.'/../../../../database/migrations/2026_09_15_000000_create_supplier_fuel_invoice_bank_payment_foundation.php');
        self::assertIsString($model);
        self::assertIsString($event);
        self::assertIsString($migration);
        foreach (['billing_document_id', 'bank_transaction_evidence_id', 'bank_transaction_evidence_revision', 'allocated_amount_minor', 'currency', 'status', 'idempotency_key', 'command_fingerprint', 'revision'] as $marker) {
            self::assertStringContainsString($marker, $model.$migration);
        }
        self::assertStringContainsString('append-only', $event);
        self::assertStringContainsString("STATUS_ACTIVE = 'active'", $model);
        self::assertStringContainsString("STATUS_REVERSED = 'reversed'", $model);
        self::assertStringContainsString('allocated_amount_minor > 0', $migration);
        self::assertStringNotContainsString('VehicleCostAllocationBankMatchingExecution', $model.$event.$migration);
        self::assertStringNotContainsString("'payment_marked' => true", $model.$event.$migration);
        self::assertStringNotContainsString('FinancialSettlementStatement::query()', $model.$event.$migration);
    }
}
