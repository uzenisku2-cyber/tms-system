<?php

namespace Tests\Unit\Modules\Routes;

use Tests\TestCase;

final class RouteCatalogCrudSourceContractTest extends TestCase
{
    public function test_route_catalog_controller_has_permission_protected_write_contract(): void
    {
        $source = file_get_contents(base_path('app/Modules/Routes/Controllers/RouteCatalogController.php'));

        self::assertIsString($source);
        self::assertStringContainsString("settings.catalogs.manage", $source);
        self::assertStringContainsString('authorizeManage', $source);
        self::assertStringContainsString('->can(', $source);
        self::assertStringNotContainsString("hasRole('super-admin')", $source);
    }

    public function test_route_changes_create_version_instead_of_overwriting_history(): void
    {
        $source = file_get_contents(base_path('app/Modules/Routes/Controllers/RouteCatalogController.php'));

        self::assertIsString($source);
        self::assertStringContainsString('addVersion', $source);
        self::assertStringContainsString('valid_from', $source);
        self::assertStringContainsString('change_note', $source);
    }

    public function test_route_catalog_permission_is_declared_only_in_central_permission_list_for_super_admin_inheritance(): void
    {
        $source = file_get_contents(base_path('database/seeders/RolePermissionSeeder.php'));

        self::assertIsString($source);
        self::assertStringContainsString("'settings.catalogs.manage'", $source);
        self::assertStringContainsString("'super-admin' => self::PERMISSIONS", $source);
    }

    public function test_editable_route_catalog_uses_existing_bearer_auth_and_api_actions(): void
    {
        $source = file_get_contents(base_path('resources/views/mvp/settings-route-catalog-crud.blade.php'));

        self::assertIsString($source);
        self::assertStringContainsString('sessionStorage.getItem', $source);
        self::assertStringContainsString('Bearer ${token}', $source);
        self::assertStringContainsString('/api/v1/settings/catalogs/routes', $source);
        self::assertStringContainsString('Přidat trasu', $source);
        self::assertStringContainsString('Uložit novou verzi', $source);
        self::assertStringContainsString('Aktivovat trasu', $source);
    }
}
