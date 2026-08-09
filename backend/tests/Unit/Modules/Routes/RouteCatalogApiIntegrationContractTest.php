<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Routes;

use PHPUnit\Framework\TestCase;

final class RouteCatalogApiIntegrationContractTest extends TestCase
{
    private function backendRoot(): string
    {
        return dirname(__DIR__, 4);
    }

    public function test_route_catalog_api_routes_use_sanctum_and_organization_context(): void
    {
        $source = file_get_contents($this->backendRoot().'/routes/api.php');

        self::assertIsString($source);
        self::assertStringContainsString('S020-04F3A4E2 ROUTE CATALOG API', $source);
        self::assertStringContainsString("prefix('v1/settings/catalogs/routes')", $source);
        self::assertStringContainsString("'auth:sanctum'", $source);
        self::assertStringContainsString('ResolveOrganizationContext::class', $source);
        self::assertStringContainsString("RouteCatalogController::class, 'apiIndex'", $source);
        self::assertStringContainsString("RouteCatalogController::class, 'store'", $source);
        self::assertStringContainsString("RouteCatalogController::class, 'update'", $source);
        self::assertStringContainsString("RouteCatalogController::class, 'setActive'", $source);
        self::assertStringContainsString('api.v1.settings.catalogs.routes.index', $source);
        self::assertStringContainsString('api.v1.settings.catalogs.routes.store', $source);
        self::assertStringContainsString('api.v1.settings.catalogs.routes.update', $source);
        self::assertStringContainsString('api.v1.settings.catalogs.routes.active', $source);
    }

    public function test_web_route_is_ui_shell_without_route_catalog_write_routes(): void
    {
        $source = file_get_contents($this->backendRoot().'/routes/web.php');

        self::assertIsString($source);
        self::assertStringContainsString(
            "Route::view('/settings/catalogs/routes', 'mvp.settings-route-catalog-crud')",
            $source,
        );
        self::assertStringNotContainsString(
            "Route::post('/settings/catalogs/routes'",
            $source,
        );
        self::assertStringNotContainsString(
            "Route::patch('/settings/catalogs/routes",
            $source,
        );
    }

    public function test_controller_exposes_api_index_and_preserves_permission_protected_writes(): void
    {
        $source = file_get_contents(
            $this->backendRoot().'/app/Modules/Routes/Controllers/RouteCatalogController.php',
        );

        self::assertIsString($source);
        self::assertStringContainsString('function apiIndex(', $source);
        self::assertStringContainsString('settings.catalogs.manage', $source);
        self::assertStringContainsString('authorizeManage(', $source);
        self::assertStringContainsString('->can(', $source);
    }

    public function test_ui_reuses_mvp_bearer_token_and_route_catalog_api(): void
    {
        $source = file_get_contents(
            $this->backendRoot().'/resources/views/mvp/settings-route-catalog-crud.blade.php',
        );

        self::assertIsString($source);
        self::assertStringContainsString('sessionStorage.getItem(tokenKey)', $source);
        self::assertStringContainsString('Bearer ${token}', $source);
        self::assertStringContainsString('/api/v1/settings/catalogs/routes', $source);
        self::assertStringNotContainsString('$canManage', $source);
        self::assertStringNotContainsString('$routes', $source);
    }
}
