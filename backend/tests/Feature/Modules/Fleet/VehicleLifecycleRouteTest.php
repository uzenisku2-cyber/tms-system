<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class VehicleLifecycleRouteTest extends TestCase
{
    public function test_registry_transition_exists_and_legacy_delete_is_absent(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes());
        self::assertTrue($routes->contains(fn ($route): bool => $route->uri() === 'api/v1/vehicle-registry-administration/{vehicle}/lifecycle' && in_array('PUT', $route->methods(), true)));
        self::assertFalse($routes->contains(fn ($route): bool => $route->uri() === 'api/v1/vehicles/{vehicle}' && in_array('DELETE', $route->methods(), true)));
    }
}
