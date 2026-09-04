<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fuel;

use Tests\TestCase;

final class FuelTransactionExportAuditContractTest extends TestCase
{
    public function test_export_audit_is_append_only_scoped_and_sensitive_data_safe(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_09_04_010000_create_fuel_transaction_export_events.php'));
        $service = file_get_contents(app_path('Modules/Fuel/Services/FuelTransactionExportAuditService.php'));
        $controller = file_get_contents(app_path('Modules/Fuel/Controllers/FuelTransactionController.php'));
        $routes = file_get_contents(app_path('Modules/Fuel/Routes/api.php'));
        $csv = file_get_contents(app_path('Modules/Fuel/Services/FuelTransactionCsvExportService.php'));
        foreach ([$migration, $service, $controller, $routes, $csv] as $source) {
            self::assertIsString($source);
        }
        foreach (['organization_id', 'exported_by_user_id', 'filters', 'row_count', 'exported_at'] as $marker) {
            self::assertStringContainsString($marker, $migration.$service);
        }
        self::assertStringContainsString("where('organization_id', \$organizationId)", $service);
        self::assertStringContainsString("'card_last_four'", $service);
        self::assertStringNotContainsString("'raw_payload'", $service);
        self::assertStringNotContainsString('update(', $service);
        self::assertStringNotContainsString('delete(', $service);
        self::assertStringContainsString('recordSuccessful', $controller);
        self::assertStringContainsString('public function write(iterable $items, mixed $output): int', $csv);
        self::assertStringContainsString("Route::get('/export-history'", $routes);
        self::assertStringContainsString("middleware('perm:compensation.view')", $routes);
    }
}
