<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

final class VehicleRegistryAdministrationContractTest extends TestCase
{
    public function test_administration_preserves_vehicle_registry_boundaries(): void
    {
        $root = dirname(__DIR__, 4);
        $service = file_get_contents($root.'/app/Modules/Fleet/Services/VehicleRegistryAdministrationReadService.php');
        $routes = file_get_contents($root.'/app/Modules/Fleet/Routes/api.php');
        $view = file_get_contents($root.'/resources/views/mvp/vehicle-registry-administration.blade.php');
        self::assertIsString($service);
        self::assertStringContainsString("can('vehicle.view')", $service);
        self::assertStringContainsString('organization_context_id', $service);
        self::assertStringNotContainsString('user_id', $service);
        self::assertStringNotContainsString('delete()', $service);
        self::assertStringContainsString('vehicle-registry-administration', $routes);
        self::assertStringContainsString('data-detail-section', $view);
        self::assertStringContainsString('const localizedValues=Object.freeze', $view);
        self::assertStringContainsString('vehicle_registered:\'Vozidlo zaregistrov\\u00e1no\'', $view);
        self::assertStringContainsString('localizedValue(v.lifecycle_status)', $view);
        self::assertStringContainsString('Pojistn\\u00e9 ud\\u00e1losti a incidenty', $view);
        self::assertStringNotContainsString('JSON.stringify(x||[],null,2)', $view);
    }
}
