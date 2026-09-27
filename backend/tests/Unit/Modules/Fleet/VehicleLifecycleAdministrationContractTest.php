<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

final class VehicleLifecycleAdministrationContractTest extends TestCase
{
    public function test_lifecycle_has_revision_audit_and_no_legacy_delete_route(): void
    {
        $root = __DIR__.'/../../../../';
        $service = file_get_contents($root.'app/Modules/Fleet/Services/VehicleLifecycleTransitionService.php');
        $routes = file_get_contents($root.'app/Modules/Fleet/Routes/api.php');
        $legacy = file_get_contents($root.'app/Modules/Fleet/Controllers/VehicleController.php');
        self::assertIsString($service);
        self::assertIsString($routes);
        self::assertIsString($legacy);
        foreach (['lockForUpdate()', 'expected_revision', 'hasActiveTrip()', 'current_revision', 'archived_at', 'vehicle_lifecycle_transitioned', 'VehicleRegistryEvent::query()->create'] as $marker) {
            self::assertStringContainsString($marker, $service);
        }
        self::assertStringContainsString('organization_context_id', $service);
        self::assertStringContainsString("->except(['destroy'])", $routes);
        self::assertStringContainsString("'active' => ['prohibited']", $legacy);
        self::assertStringNotContainsString('$vehicle->delete()', $legacy);
    }
}
