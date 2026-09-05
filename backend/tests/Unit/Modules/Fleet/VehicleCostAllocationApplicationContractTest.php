<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

final class VehicleCostAllocationApplicationContractTest extends TestCase
{
    public function test_application_contract_is_authorized_revisioned_and_financially_non_automating(): void
    {
        $service = file_get_contents(__DIR__.'/../../../../app/Modules/Fleet/Services/VehicleCostAllocationApplicationService.php');
        $routes = file_get_contents(__DIR__.'/../../../../app/Modules/Fleet/Routes/api.php');
        self::assertIsString($service);
        $request = file_get_contents(__DIR__.'/../../../../app/Modules/Fleet/Requests/StoreVehicleCostAllocationRequest.php');
        self::assertIsString($request);
        self::assertIsString($routes);
        foreach (['findManageableOrganization', 'findVisibleDriver', 'compensation.manage', 'expected_revision', 'lockForUpdate', 'financial_automation', 'invoice_required', 'deposit_offset', 'repair_fund_reserve'] as $marker) {
            self::assertStringContainsString($marker, $service.$request);
        }
        foreach (['vehicle-cost-allocations.store', 'vehicle-cost-allocations.show', 'vehicle-cost-allocations.approve'] as $marker) {
            self::assertStringContainsString($marker, $routes);
        }
        foreach (['FinancialCalculation::query()->create', 'BillingDocument::query()->create', 'bank_transaction_id', 'payment_id', 'repair_fund_transaction_id'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $service);
        }
    }
}
