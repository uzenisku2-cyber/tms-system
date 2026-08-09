<?php

namespace Tests\Unit\Modules\Routes;

use App\Modules\Routes\Models\Route;
use App\Modules\Routes\Models\RouteVersion;
use App\Modules\Routes\Services\RouteCatalogService;
use Tests\TestCase;

class RouteCatalogModelContractTest extends TestCase
{
    public function test_route_uses_stable_identity_separate_from_versioned_attributes(): void
    {
        $route = new Route();

        $this->assertSame('routes', $route->getTable());
        $this->assertContains('active', $route->getFillable());
        $this->assertNotContains('route_uid', $route->getFillable());
        $this->assertSame('boolean', $route->getCasts()['active']);
    }

    public function test_route_version_contains_historically_versioned_route_attributes(): void
    {
        $version = new RouteVersion();

        $this->assertSame('route_versions', $version->getTable());

        foreach ([
            'route_number',
            'route_name',
            'area',
            'valid_from',
            'valid_to',
            'change_type',
            'change_note',
        ] as $attribute) {
            $this->assertContains($attribute, $version->getFillable());
        }

        $this->assertSame('date', $version->getCasts()['valid_from']);
        $this->assertSame('date', $version->getCasts()['valid_to']);
    }

    public function test_route_catalog_service_exists_without_daily_report_rewrite_dependency(): void
    {
        $this->assertTrue(class_exists(RouteCatalogService::class));

        $serviceSource = file_get_contents(
            app_path('Modules/Routes/Services/RouteCatalogService.php')
        );

        $this->assertIsString($serviceSource);
        $this->assertStringContainsString('DB::transaction', $serviceSource);
        $this->assertStringNotContainsString('DailyReport', $serviceSource);
        $this->assertStringNotContainsString('route_number = ', $serviceSource);
    }
}
