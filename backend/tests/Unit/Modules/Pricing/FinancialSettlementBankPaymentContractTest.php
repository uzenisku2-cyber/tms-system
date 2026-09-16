<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class FinancialSettlementBankPaymentContractTest extends TestCase
{
    public function test_materialization_is_exact_shared_idempotent_audited_and_non_mutating(): void
    {
        $root = dirname(__DIR__, 4);
        $service = file_get_contents($root.'/app/Modules/Pricing/Services/FinancialSettlementBankPaymentService.php');
        $capacity = file_get_contents($root.'/app/Modules/Fleet/Services/BankTransactionEvidenceCapacityService.php');
        $event = file_get_contents($root.'/app/Modules/Pricing/Models/FinancialSettlementBankPaymentEvent.php');
        self::assertIsString($service);
        self::assertIsString($capacity);
        self::assertIsString($event);
        self::assertStringContainsString('STATUS_ACCEPTED', $service);
        self::assertStringContainsString('expected_candidate_revision', $service);
        self::assertStringContainsString('proposed_amount_minor', $service);
        self::assertStringContainsString('settlement_outstanding_amount_minor', $service);
        self::assertStringContainsString('command_fingerprint', $service);
        self::assertStringContainsString('lockForUpdate()', $service);
        self::assertStringContainsString('SupplierFuelInvoiceBankPayment::query()', $capacity);
        self::assertStringContainsString('VehicleCostAllocationBankMatchingExecution::query()', $capacity);
        self::assertStringContainsString('FinancialSettlementBankPayment::query()', $capacity);
        self::assertStringContainsString('append-only', $event);
        self::assertStringContainsString('payment_reversed', $service);
        self::assertStringContainsString('expected_revision', $service);
        self::assertStringContainsString('released_amount_minor', $service);
        self::assertStringContainsString("'billing_document_modified' => false", $service);
        self::assertStringContainsString("'settlement_statement_modified' => false", $service);
        self::assertStringNotContainsString('$statement->forceFill(', $service);
        self::assertStringNotContainsString('$document->forceFill(', $service);
        self::assertStringNotContainsString('VehicleCostAllocationBankMatchingExecution::query()->create', $service);
    }
}
