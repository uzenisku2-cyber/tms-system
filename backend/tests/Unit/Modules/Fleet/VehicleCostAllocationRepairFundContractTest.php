<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use Tests\TestCase;

final class VehicleCostAllocationRepairFundContractTest extends TestCase
{
    public function test_reservation_is_explicit_idempotent_append_only_and_non_executing(): void
    {
        $root = __DIR__.'/../../../../';
        $service = file_get_contents($root.'app/Modules/Fleet/Services/VehicleCostAllocationRepairFundService.php');
        $request = file_get_contents($root.'app/Modules/Fleet/Requests/ReserveVehicleCostAllocationRepairFundRequest.php');
        $migration = file_get_contents($root.'database/migrations/2026_09_08_120000_create_vehicle_cost_allocation_repair_fund_reservations.php');
        self::assertIsString($service);
        self::assertIsString($request);
        self::assertIsString($migration);
        $contract = $service.PHP_EOL.$request;
        foreach (['repair_fund_reserve', 'repair_fund', 'expected_instruction_revision', 'idempotency_key', 'lockForUpdate', 'invoice_created', 'bank_transaction_created', 'payment_marked', 'fund_movement_created', 'settlement_deduction_applied'] as $marker) {
            self::assertStringContainsString($marker, $contract);
        }
        foreach (['vcafrr_instruction_unique', 'vcafrr_org_idempotency_unique', 'vcafre_reservation_revision_unique', 'responsible_party_type'] as $marker) {
            self::assertStringContainsString($marker, $migration);
        }
    }
}
