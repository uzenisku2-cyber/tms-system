<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ProgressiveVehicleRecordFoundationTest extends TestCase
{
    public function test_progressive_vehicle_routes_exist_without_delete_action(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route): bool => str_contains($route->uri(), 'vehicle-registry-administration'));
        self::assertTrue($routes->contains(fn ($route): bool => in_array('POST', $route->methods(), true) && $route->uri() === 'api/v1/vehicle-registry-administration'));
        self::assertTrue($routes->contains(fn ($route): bool => in_array('PATCH', $route->methods(), true) && $route->uri() === 'api/v1/vehicle-registry-administration/{vehicle}'));
        self::assertTrue($routes->contains(fn ($route): bool => in_array('PUT', $route->methods(), true) && $route->uri() === 'api/v1/vehicle-registry-administration/{vehicle}/field-statuses/{fieldKey}'));
        self::assertFalse($routes->contains(fn ($route): bool => in_array('DELETE', $route->methods(), true)));
    }
}
