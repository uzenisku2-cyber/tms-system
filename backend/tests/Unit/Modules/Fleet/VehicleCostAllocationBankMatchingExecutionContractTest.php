<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

final class VehicleCostAllocationBankMatchingExecutionContractTest extends TestCase
{
    public function test_execution_is_explicit_idempotent_append_only_and_does_not_mark_payment(): void
    {
        $root = dirname(__DIR__, 4).'/';
        $service = file_get_contents($root.'app/Modules/Fleet/Services/VehicleCostAllocationBankMatchingExecutionService.php');
        $migration = file_get_contents($root.'database/migrations/2026_09_09_180000_create_vehicle_cost_allocation_bank_matching_executions.php');
        $routes = file_get_contents($root.'app/Modules/Fleet/Routes/api.php');

        self::assertIsString($service);
        self::assertIsString($migration);
        self::assertIsString($routes);
        foreach (['expected_handoff_revision', 'expected_bank_transaction_evidence_revision', 'idempotency_key', 'lockForUpdate', 'findManageableOrganization', 'findVisibleDriver', 'manual_execution', 'bank_matching_performed', 'payment_marked', 'billing_document_modified', 'deposit_offset_performed', 'repair_fund_movement_performed'] as $marker) {
            self::assertStringContainsString($marker, $service);
        }
        foreach (['vcabme_handoff_unique', 'vcabme_org_idempotency_unique', 'vcabmee_execution_revision_unique', 'vcabme_values_check'] as $marker) {
            self::assertStringContainsString($marker, $migration);
        }
        self::assertStringContainsString('vehicle-cost-allocation-bank-matching-handoffs/{handoffPublicId}/execute', $routes);
        foreach (['BillingDocument::query()->update', 'BankTransactionEvidence::query()->update', 'VehicleCostAllocationBankMatchingHandoff::query()->update', 'BillingPayment::query()->', 'VehicleCostAllocationDepositOffsetAcknowledgement::query()->', 'VehicleCostAllocationRepairFundReservation::query()->'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $service);
        }
    }
}
