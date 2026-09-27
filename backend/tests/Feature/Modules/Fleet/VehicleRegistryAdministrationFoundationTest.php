<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class VehicleRegistryAdministrationFoundationTest extends TestCase
{
    public function test_vehicle_registry_administration_page_renders(): void
    {
        $this->get('/settings/vehicles')->assertOk()
            ->assertSee('Registr vozidel')
            ->assertSee('Založit nové vozidlo')
            ->assertSee('Správa vozidel')
            ->assertSee('Životní cyklus')
            ->assertSee('data-registry-folder="create"', false)
            ->assertSee('data-registry-folder="manage"', false);
    }

    public function test_vehicle_registry_administration_routes_are_installed_without_delete_action(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route): bool => str_contains($route->uri(), 'vehicle-registry-administration'));
        self::assertCount(12, $routes);
        foreach ([
            ['GET', 'api/v1/vehicle-registry-administration'],
            ['GET', 'api/v1/vehicle-registry-administration/{vehicle}'],
            ['POST', 'api/v1/vehicle-registry-administration'],
            ['PATCH', 'api/v1/vehicle-registry-administration/{vehicle}'],
            ['PUT', 'api/v1/vehicle-registry-administration/{vehicle}/lifecycle'],
            ['PUT', 'api/v1/vehicle-registry-administration/{vehicle}/field-statuses/{fieldKey}'],
            ['POST', 'api/v1/vehicle-registry-administration/{vehicle}/documents'],
            ['PUT', 'api/v1/vehicle-registry-administration/{vehicle}/documents/{document}/verification'],
            ['POST', 'api/v1/vehicle-registry-administration/{vehicle}/ownerships'],
            ['PUT', 'api/v1/vehicle-registry-administration/{vehicle}/ownerships/{ownership}/verification'],
            ['POST', 'api/v1/vehicle-registry-administration/{vehicle}/responsibilities'],
            ['PUT', 'api/v1/vehicle-registry-administration/{vehicle}/responsibilities/{responsibility}/status'],
        ] as [$method, $uri]) {
            self::assertTrue($routes->contains(fn ($route): bool => in_array($method, $route->methods(), true) && $route->uri() === $uri));
        }
        self::assertFalse($routes->contains(fn ($route): bool => in_array('DELETE', $route->methods(), true)));
    }
}
