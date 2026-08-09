<?php

namespace Tests\Unit\Modules\Routes;

use Tests\TestCase;

final class RouteCatalogWebActivationContractTest extends TestCase
{
    public function test_web_route_is_a_shell_and_write_routes_are_not_exposed_on_web_guard(): void
    {
        $source = file_get_contents(base_path('routes/web.php'));

        self::assertIsString($source);
        self::assertStringContainsString(
            "Route::view('/settings/catalogs/routes', 'mvp.settings-route-catalog-crud')",
            $source,
        );
        self::assertStringNotContainsString("Route::post('/settings/catalogs/routes'", $source);
        self::assertStringNotContainsString("Route::patch('/settings/catalogs/routes/{route}'", $source);
        self::assertStringNotContainsString("Route::patch('/settings/catalogs/routes/{route}/active'", $source);
    }

    public function test_route_catalog_api_uses_sanctum_and_organization_context(): void
    {
        $source = file_get_contents(base_path('routes/api.php'));

        self::assertIsString($source);
        self::assertStringContainsString('S020-04F3A4E2 ROUTE CATALOG API', $source);
        self::assertStringContainsString("'auth:sanctum'", $source);
        self::assertStringContainsString('ResolveOrganizationContext::class', $source);
        self::assertStringContainsString("'apiIndex'", $source);
        self::assertStringContainsString("'store'", $source);
        self::assertStringContainsString("'update'", $source);
        self::assertStringContainsString("'setActive'", $source);
    }

    public function test_api_read_contract_exposes_permission_result_from_authenticated_request(): void
    {
        $source = file_get_contents(base_path('app/Modules/Routes/Controllers/RouteCatalogController.php'));

        self::assertIsString($source);
        self::assertStringContainsString('function apiIndex', $source);
        self::assertStringContainsString("'can_manage'", $source);
        self::assertStringContainsString('self::MANAGE_PERMISSION', $source);
    }
}
