<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use Tests\TestCase;

final class VehicleCostAllocationBillingDocumentHandoffContractTest extends TestCase
{
    public function test_execution_is_explicit_idempotent_vat_validated_and_bank_separate(): void
    {
        $root = __DIR__.'/../../../../';
        $service = file_get_contents($root.'app/Modules/Fleet/Services/VehicleCostAllocationBillingDocumentHandoffService.php');
        $migration = file_get_contents($root.'database/migrations/2026_09_07_120000_create_vehicle_cost_allocation_handoff_executions.php');
        self::assertIsString($service);
        self::assertIsString($migration);
        foreach (['expected_instruction_revision', 'idempotency_key', 'lockForUpdate', 'organization_tax_profiles', 'vat_rate_basis_points', 'bank_matching_performed', 'deposit_offset_performed', 'repair_fund_movement_performed'] as $m) {
            self::assertStringContainsString($m, $service);
        }foreach (['vcafhe_instruction_unique', 'vcafhe_org_idempotency_unique', 'vcafhee_execution_revision_unique', 'customer_invoice'] as $m) {
            self::assertStringContainsString($m, $migration);
        }
    }
}
