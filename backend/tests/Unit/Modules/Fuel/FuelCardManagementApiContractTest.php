<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fuel;

use Tests\TestCase;

final class FuelCardManagementApiContractTest extends TestCase
{
    public function test_management_api_contract_is_registered_and_audited(): void
    {
        $routes = file_get_contents(base_path('app/Modules/Fuel/Routes/api.php'));
        $service = file_get_contents(base_path('app/Modules/Fuel/Services/FuelCardManagementService.php'));
        $gateway = file_get_contents(base_path('routes/api.php'));
        self::assertIsString($routes);
        self::assertIsString($service);
        self::assertIsString($gateway);
        self::assertStringContainsString("app_path('Modules/Fuel/Routes/api.php')", $gateway);
        foreach (['index', 'show', 'store', 'changeStatus', 'assign', 'endAssignment', 'storePolicy'] as $action) {
            self::assertStringContainsString("'{$action}'", $routes);
        }
        foreach (['lockForUpdate', 'overlapping active assignment', 'status_changed', 'assignment_created', 'assignment_ended', 'settlement_policy_created', 'counterparty_tax_profile'] as $marker) {
            self::assertStringContainsString($marker, $service);
        }
    }
}
