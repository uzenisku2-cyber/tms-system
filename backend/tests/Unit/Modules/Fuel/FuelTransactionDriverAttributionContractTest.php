<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fuel;

use Tests\TestCase;

final class FuelTransactionDriverAttributionContractTest extends TestCase
{
    public function test_driver_attribution_is_append_only_scoped_and_financially_immutable(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_08_30_010000_create_fuel_transaction_driver_attributions.php'));
        $service = file_get_contents(app_path('Modules/Fuel/Services/FuelTransactionDriverAttributionService.php'));
        $routes = file_get_contents(app_path('Modules/Fuel/Routes/api.php'));
        $request = file_get_contents(app_path('Modules/Fuel/Requests/StoreFuelTransactionDriverAttributionRequest.php'));
        self::assertIsString($migration); self::assertIsString($service); self::assertIsString($routes); self::assertIsString($request);
        foreach (['actual_driver_id', 'actual_driver_organization_assignment_id', 'driver_attribution_revision', 'previous_driver_id', 'new_driver_id', 'corrected_by_user_id', 'corrected_at'] as $marker) {
            self::assertStringContainsString($marker, $migration);
        }
        self::assertStringContainsString('visibleDriverOrganizationAssignmentIds', $service);
        self::assertStringContainsString('findVisibleDriver', $service);
        self::assertStringContainsString("whereDate('valid_from', '<=', \$date)", $service);
        self::assertStringContainsString('expected_revision', $request);
        self::assertStringContainsString('driver-attributions', $routes);
        self::assertStringNotContainsString("forceFill(['quantity'", $service);
        self::assertStringNotContainsString("forceFill(['gross_amount'", $service);
    }
}
