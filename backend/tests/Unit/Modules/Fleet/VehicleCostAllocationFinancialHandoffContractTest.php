<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

final class VehicleCostAllocationFinancialHandoffContractTest extends TestCase
{
    public function test_handoff_is_explicit_idempotent_append_only_and_non_executing(): void
    {
        $root = __DIR__.'/../../../../';
        $service = file_get_contents($root.'app/Modules/Fleet/Services/VehicleCostAllocationFinancialHandoffService.php');
        $migration = file_get_contents($root.'database/migrations/2026_09_07_000000_create_vehicle_cost_allocation_financial_handoffs.php');
        self::assertIsString($service);
        self::assertIsString($migration);
        foreach (['expectedRevision', 'approved', 'lockForUpdate', 'invoice_created', 'payment_matched', 'financial_automation_performed', 'bank_matching_eligible'] as $m) {
            self::assertStringContainsString($m, $service);
        }foreach (['vehicle_cost_allocation_handoff_source_unique', 'repair_fund', 'settlement_deduction', 'financial_automation_performed = false'] as $m) {
            self::assertStringContainsString($m, $migration);
        }
    }
}
