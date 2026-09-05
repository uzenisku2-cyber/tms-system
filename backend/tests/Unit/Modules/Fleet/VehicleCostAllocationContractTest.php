<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

class VehicleCostAllocationContractTest extends TestCase
{
    public function test_cost_allocation_is_revisioned_explicit_and_financially_separate(): void
    {
        $migration = file_get_contents(__DIR__.'/../../../../database/migrations/2026_09_06_180000_create_vehicle_cost_allocation_financial_responsibility_foundation.php');
        self::assertIsString($migration);
        foreach (['service', 'incident', 'insurance', 'rental', 'leasing', 'installment', 'manual', 'responsible_party_type', 'organization', 'driver', 'insurer', 'state', 'net_amount', 'vat_amount', 'gross_amount', 'deductible', 'invoice_required', 'deposit_offset', 'repair_fund_reserve', 'manual_review', 'vat_treatment', 'vehicle_cost_allocation_event_revision_unique'] as $marker) {
            self::assertStringContainsString($marker, $migration);
        }
        foreach (['VehicleCostAllocation.php', 'VehicleCostAllocationLine.php', 'VehicleCostAllocationEvent.php'] as $model) {
            $source = file_get_contents(__DIR__.'/../../../../app/Modules/Fleet/Models/'.$model);
            self::assertIsString($source);
            self::assertStringContainsString('append-only', $source);
        }
        foreach (['financial_calculation_id', 'billing_document_id', 'bank_transaction_id', 'payment_id', 'tax_document_id', 'repair_fund_transaction_id'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $migration);
        }
    }
}
