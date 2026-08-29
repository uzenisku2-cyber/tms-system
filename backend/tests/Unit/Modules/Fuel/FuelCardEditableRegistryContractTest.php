<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fuel;

use Tests\TestCase;

final class FuelCardEditableRegistryContractTest extends TestCase
{
    public function test_update_contract_keeps_provider_identity_immutable(): void
    {
        $request = file_get_contents(app_path('Modules/Fuel/Requests/UpdateFuelCardRequest.php'));
        $service = file_get_contents(app_path('Modules/Fuel/Services/FuelCardManagementService.php'));

        self::assertIsString($request);
        self::assertIsString($service);
        self::assertStringNotContainsString("'provider' =>", $request);
        self::assertStringNotContainsString("'provider_card_identifier' =>", $request);
        self::assertStringContainsString("'provider_status' =>", $request);
        self::assertStringContainsString("'provider_status_verified_at'", $service);
        self::assertStringContainsString("\$this->event(\$locked, 'updated'", $service);
    }
}
