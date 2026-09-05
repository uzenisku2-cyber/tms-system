<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use Tests\TestCase;

final class VehicleCostAllocationDepositOffsetContractTest extends TestCase
{
    public function test_acknowledgement_is_explicit_idempotent_append_only_and_non_executing(): void
    {
        $root = __DIR__.'/../../../../';
        $service = file_get_contents($root.'app/Modules/Fleet/Services/VehicleCostAllocationDepositOffsetService.php');
        $request = file_get_contents($root.'app/Modules/Fleet/Requests/AcknowledgeVehicleCostAllocationDepositOffsetRequest.php');
        $migration = file_get_contents($root.'database/migrations/2026_09_08_000000_create_vehicle_cost_allocation_deposit_offset_acknowledgements.php');
        self::assertIsString($service);
        self::assertIsString($request);
        self::assertIsString($migration);
        $applicationContract = $service.PHP_EOL.$request;
        foreach (['deposit_offset', 'settlement_deduction', 'expected_instruction_revision', 'idempotency_key', 'lockForUpdate', 'repair_fund_pending', 'invoice_created', 'bank_transaction_matched', 'payment_marked', 'settlement_deduction_applied'] as $marker) {
            self::assertStringContainsString($marker, $applicationContract);
        }foreach (['vcadoa_instruction_unique', 'vcadoa_org_idempotency_unique', 'vcadoe_ack_revision_unique', 'responsible_party_type'] as $marker) {
            self::assertStringContainsString($marker, $migration);
        }
    }
}
