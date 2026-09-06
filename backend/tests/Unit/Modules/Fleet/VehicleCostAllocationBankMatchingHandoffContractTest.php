<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

final class VehicleCostAllocationBankMatchingHandoffContractTest extends TestCase
{
    public function test_handoff_is_explicit_idempotent_append_only_and_non_executing(): void
    {
        $root = __DIR__.'/../../../../';
        $service = file_get_contents($root.'app/Modules/Fleet/Services/VehicleCostAllocationBankMatchingHandoffService.php');
        $migration = file_get_contents($root.'database/migrations/2026_09_09_000000_create_vehicle_cost_allocation_bank_matching_handoffs.php');
        self::assertIsString($service);
        self::assertIsString($migration);
        foreach (['expected_instruction_revision', 'idempotency_key', 'lockForUpdate', 'findManageableOrganization', 'findVisibleDriver', 'bank_matching_performed', 'payment_marked', 'invoice_created', 'deposit_offset_performed', 'repair_fund_movement_performed'] as $marker) {
            self::assertStringContainsString($marker, $service);
        }foreach (['vcabmh_org_idempotency_unique', 'vcabmh_instruction_bank_ref_unique', 'vcabmhe_handoff_revision_unique', 'vcabmh_values_check'] as $marker) {
            self::assertStringContainsString($marker, $migration);
        }
    }
}
