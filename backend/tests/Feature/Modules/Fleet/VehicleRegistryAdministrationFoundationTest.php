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
            ->assertSee('Technické kontroly')
            ->assertSee('Pojištění')
            ->assertSee('Servis')
            ->assertSee('Incidenty')
            ->assertSee('Poskytnutí vozidla')
            ->assertSee('Ceny poskytnutí vozidla')
            ->assertSee('Financování')
            ->assertSee('Splátkové kalendáře')
            ->assertSee('Jednotlivé splátky')
            ->assertSee('data-registry-folder="create"', false)
            ->assertSee('data-registry-folder="manage"', false);
    }

    public function test_embedded_vehicle_registry_keeps_management_without_settings_navigation(): void
    {
        $this->get('/settings/vehicles?embedded=1')->assertOk()
            ->assertSee('data-registry-folder="create"', false)
            ->assertSee('data-registry-folder="manage"', false)
            ->assertDontSee('href="/settings"', false)
            ->assertDontSee('<h1>Registr vozidel</h1>', false);

        $this->get('/settings/vehicles')->assertOk()
            ->assertSee('href="/settings"', false)
            ->assertSee('<h1>Registr vozidel</h1>', false);
    }

    public function test_vehicle_registry_administration_routes_are_installed_without_delete_action(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route): bool => str_contains($route->uri(), 'vehicle-registry-administration'));
        self::assertCount(32, $routes);
        foreach ([
            ['GET', 'api/v1/vehicle-registry-administration'],
            ['GET', 'api/v1/vehicle-registry-administration/{vehicle}'],
            ['POST', 'api/v1/vehicle-registry-administration'],
            ['PATCH', 'api/v1/vehicle-registry-administration/{vehicle}'],
            ['PUT', 'api/v1/vehicle-registry-administration/{vehicle}/lifecycle'],
            ['PUT', 'api/v1/vehicle-registry-administration/{vehicle}/field-statuses/{fieldKey}'],
            ['POST', 'api/v1/vehicle-registry-administration/{vehicle}/documents'],
            ['GET', 'api/v1/vehicle-registry-administration/{vehicle}/documents/{document}/download'],
            ['PUT', 'api/v1/vehicle-registry-administration/{vehicle}/documents/{document}/verification'],
            ['POST', 'api/v1/vehicle-registry-administration/{vehicle}/compliance-records'],
            ['PUT', 'api/v1/vehicle-registry-administration/{vehicle}/compliance-records/{record}/revisions'],
            ['POST', 'api/v1/vehicle-registry-administration/{vehicle}/insurance-policies'],
            ['PUT', 'api/v1/vehicle-registry-administration/{vehicle}/insurance-policies/{record}/revisions'],
            ['POST', 'api/v1/vehicle-registry-administration/{vehicle}/service-records'],
            ['PUT', 'api/v1/vehicle-registry-administration/{vehicle}/service-records/{record}/revisions'],
            ['POST', 'api/v1/vehicle-registry-administration/{vehicle}/incidents'],
            ['PUT', 'api/v1/vehicle-registry-administration/{vehicle}/incidents/{record}/revisions'],
            ['POST', 'api/v1/vehicle-registry-administration/{vehicle}/provision-agreements'],
            ['PUT', 'api/v1/vehicle-registry-administration/{vehicle}/provision-agreements/{record}/revisions'],
            ['POST', 'api/v1/vehicle-registry-administration/{vehicle}/provision-prices'],
            ['PUT', 'api/v1/vehicle-registry-administration/{vehicle}/provision-prices/{record}/revisions'],
            ['POST', 'api/v1/vehicle-registry-administration/{vehicle}/financing-agreements'],
            ['PUT', 'api/v1/vehicle-registry-administration/{vehicle}/financing-agreements/{record}/revisions'],
            ['POST', 'api/v1/vehicle-registry-administration/{vehicle}/installments'],
            ['PUT', 'api/v1/vehicle-registry-administration/{vehicle}/installments/{record}/revisions'],
            ['POST', 'api/v1/vehicle-registry-administration/{vehicle}/installment-schedules'],
            ['PUT', 'api/v1/vehicle-registry-administration/{vehicle}/installment-schedules/{record}/revisions'],
            ['POST', 'api/v1/vehicle-registry-administration/{vehicle}/ownerships'],
            ['PUT', 'api/v1/vehicle-registry-administration/{vehicle}/ownerships/{ownership}/revisions'],
            ['PUT', 'api/v1/vehicle-registry-administration/{vehicle}/ownerships/{ownership}/verification'],
            ['POST', 'api/v1/vehicle-registry-administration/{vehicle}/responsibilities'],
            ['PUT', 'api/v1/vehicle-registry-administration/{vehicle}/responsibilities/{responsibility}/status'],
        ] as [$method, $uri]) {
            self::assertTrue($routes->contains(fn ($route): bool => in_array($method, $route->methods(), true) && $route->uri() === $uri));
        }
        self::assertFalse($routes->contains(fn ($route): bool => in_array('DELETE', $route->methods(), true)));
    }
}
