<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class OperationalApplicationShellIntegrationUiTest extends TestCase
{
    public function test_driver_list_survives_an_individual_assignment_failure(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            'S038-01A RESILIENT DRIVER ASSIGNMENT ENRICHMENT',
            $source,
        );
        self::assertStringContainsString(
            'organization_assignment_load_error:',
            $source,
        );
        self::assertStringContainsString(
            "catch (error) {\n                                return {\n                                    ...driver,",
            $source,
        );
        self::assertStringContainsString(
            'const assignmentLoadFailureCount =',
            $source,
        );
        self::assertStringContainsString(
            'Historii přiřazení se nepodařilo načíst u ${assignmentLoadFailureCount} záznamů.',
            $source,
        );
    }

    public function test_driver_admin_uses_the_organization_scoped_endpoint(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            "'/api/v1/own-drivers'",
            $source,
        );
        self::assertStringContainsString(
            '`/api/v1/own-drivers/${driver.id}/assignments`',
            $source,
        );
    }

    public function test_carrier_navigation_stays_inside_unified_shell(): void
    {
        $source = file_get_contents(resource_path('views/mvp/app.blade.php'));

        self::assertIsString($source);
        self::assertStringContainsString('data-drayvia-page="carriers"', $source);
        self::assertStringContainsString('const carriers = () => `', $source);
        self::assertStringContainsString('loadCarrierShellData', $source);
        self::assertStringContainsString('drayviaCarrierShellList', $source);
        self::assertStringContainsString("api('/api/v1/organization-profile')", $source);
        self::assertStringContainsString("api('/api/v1/carriers')", $source);
        self::assertStringContainsString("if (page === 'carriers')", $source);
        self::assertStringNotContainsString("window.location.href = '/carriers';", $source);
    }

    public function test_settings_tiles_expose_real_shell_and_administration_targets(): void
    {
        $source = file_get_contents(resource_path('views/mvp/app.blade.php'));

        self::assertIsString($source);
        self::assertStringContainsString('data-drayvia-settings-target="carriers"', $source);
        self::assertStringContainsString('data-drayvia-page="daily-report-settings"', $source);
        self::assertStringContainsString("'drayviaDailyReportSettingsFrame'", $source);
        self::assertStringContainsString("'/daily-report-settings'", $source);
        self::assertStringContainsString('data-drayvia-settings-target="statistics"', $source);
        self::assertStringContainsString('data-drayvia-settings-target="overview"', $source);
        self::assertStringContainsString('data-drayvia-page="route-catalog"', $source);
        self::assertStringContainsString("'drayviaRouteCatalogFrame'", $source);
        self::assertStringContainsString("'/settings/catalogs/routes'", $source);
        self::assertStringContainsString('id="drayviaEmbeddedSettingsBack"', $source);
        self::assertStringContainsString("renderPage('settings')", $source);
        self::assertSame(1, substr_count($source, 'data-drayvia-page="settings"'));
        self::assertStringContainsString('normalizeEmbeddedSettingsFrame', $source);
        self::assertStringContainsString("document.querySelector('.sidebar')", $source);
        self::assertStringContainsString("shell.style.gridTemplateColumns = 'minmax(0, 1fr)'", $source);
        self::assertStringContainsString("if (page === 'daily-report-settings' || page === 'route-catalog')", $source);
        self::assertStringNotContainsString('href="/settings/catalogs/routes" target="_blank"', $source);
        self::assertStringContainsString('id="drayviaSettingsSystemAction"', $source);
        self::assertStringContainsString('data-drayvia-settings-target="fuel"', $source);
        self::assertStringContainsString('data-drayvia-settings-target="imports"', $source);
        self::assertStringContainsString('loadSettingsSystemInfo', $source);
        self::assertStringContainsString("api('/api/v1/auth/me')", $source);
        self::assertStringContainsString("if (page === 'settings')", $source);
    }

    public function test_fuel_page_embeds_transaction_administration_workspace(): void
    {
        $source = file_get_contents(resource_path('views/mvp/app.blade.php'));

        self::assertIsString($source);
        self::assertStringContainsString('id="drayviaFuelWorkspaceFrame"', $source);
        self::assertStringContainsString('src="/settings/fuel-transactions"', $source);
        self::assertStringContainsString('bindFuelWorkspace', $source);
        self::assertStringContainsString("if (page === 'fuel')", $source);
        self::assertStringContainsString('OBNOVIT PHM', $source);
        self::assertStringNotContainsString('Přehled importovaných transakcí bude zde.', $source);
    }
}
