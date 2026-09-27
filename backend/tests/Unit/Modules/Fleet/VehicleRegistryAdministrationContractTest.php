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
        self::assertStringNotContainsString('delete()', $service);
        self::assertStringContainsString('vehicle-registry-administration', $routes);
        self::assertStringContainsString('data-testid="vehicle-registry-administration"', $view);
        self::assertStringContainsString('data-registry-folder="create"', $view);
        self::assertStringContainsString('data-registry-folder="manage"', $view);
        self::assertStringContainsString("subtab('lifecycle'", $view);
        self::assertStringContainsString("subtab('documents'", $view);
        self::assertStringContainsString("subtab('ownership'", $view);
        self::assertStringContainsString("subtab('responsibilities'", $view);
        self::assertStringContainsString("subtab('completion'", $view);
        self::assertStringContainsString("subtab('statuses'", $view);
        self::assertStringContainsString("subtab('history'", $view);
        foreach (['lifecycleTargets=', 'can_manage_vehicles', 'expected_revision:revision', "method:'PUT'", 'window.confirm', 'error.status===409'] as $marker) {
            self::assertStringContainsString($marker, $view);
        }
        self::assertStringContainsString('const fuelTypes=', $view);
        self::assertStringContainsString("['diesel','Nafta']", $view);
        self::assertStringContainsString('expected_revision:current.vehicle.revision', $view);
        self::assertStringContainsString('expected_document_revision', $view);
        self::assertStringContainsString('expected_ownership_revision', $view);
        self::assertStringContainsString('expected_responsibility_revision', $view);
        self::assertStringNotContainsString('method:\'DELETE\'', $view);
        self::assertStringNotContainsString('JSON.stringify(x||[],null,2)', $view);
    }
}
