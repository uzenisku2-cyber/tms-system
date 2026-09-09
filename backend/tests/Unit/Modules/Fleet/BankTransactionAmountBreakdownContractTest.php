<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

final class BankTransactionAmountBreakdownContractTest extends TestCase
{
    public function test_breakdown_is_exact_versioned_audited_and_non_executing(): void
    {
        $root = dirname(__DIR__, 4).'/app/Modules/Fleet/';
        $service = file_get_contents($root.'Services/BankTransactionAmountBreakdownService.php');
        $request = file_get_contents($root.'Requests/StoreBankTransactionAmountBreakdownRequest.php');
        $model = file_get_contents($root.'Models/BankTransactionAmountBreakdown.php');

        self::assertIsString($service);
        self::assertIsString($request);
        self::assertIsString($model);
        foreach (['source_amount_minor', 'allocated_amount_minor', 'expected_revision', 'idempotency_key', 'lockForUpdate'] as $marker) {
            self::assertStringContainsString($marker, $service);
        }
        foreach (['vat', 'deductible', 'custom', 'components.*.amount'] as $marker) {
            self::assertStringContainsString($marker, $request);
        }
        foreach (['append-only', 'updating', 'deleting'] as $marker) {
            self::assertStringContainsString($marker, $model);
        }
        foreach (['markAsPaid', 'payment_id', 'BillingDocument::query()', 'VehicleCostAllocationBankMatchingExecution::query()'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $service);
        }
    }
}
