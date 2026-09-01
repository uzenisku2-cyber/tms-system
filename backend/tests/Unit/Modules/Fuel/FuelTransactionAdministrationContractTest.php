<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fuel;

use Tests\TestCase;

final class FuelTransactionAdministrationContractTest extends TestCase
{
    public function test_overview_contract_is_scoped_filterable_and_business_friendly(): void
    {
        $service = file_get_contents(app_path('Modules/Fuel/Services/FuelTransactionAdministrationService.php'));
        $request = file_get_contents(app_path('Modules/Fuel/Requests/IndexFuelTransactionRequest.php'));
        $routes = file_get_contents(app_path('Modules/Fuel/Routes/api.php'));

        self::assertIsString($service);
        self::assertIsString($request);
        self::assertIsString($routes);
        foreach (['owner_organization_id', 'date_from', 'date_to', 'provider', 'driver_id', 'card', 'search', 'masked_card', 'effective_driver', 'pagination'] as $marker) {
            self::assertStringContainsString($marker, $service.$request);
        }
        self::assertStringContainsString("Route::get('/', [FuelTransactionController::class, 'index'])", $routes);
        self::assertStringContainsString("middleware('perm:compensation.view')", $routes);
        self::assertStringNotContainsString("'raw_payload' =>", $service);
        self::assertStringNotContainsString("'normalized_payload' =>", $service);
        self::assertStringNotContainsString("'provider_card_identifier' =>", $service);
        self::assertStringNotContainsString("forceFill(['quantity'", $service);
        self::assertStringNotContainsString("forceFill(['gross_amount'", $service);
    }
}
