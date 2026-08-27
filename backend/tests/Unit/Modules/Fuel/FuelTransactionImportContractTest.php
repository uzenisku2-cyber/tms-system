<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fuel;

use Tests\TestCase;

final class FuelTransactionImportContractTest extends TestCase
{
    public function test_import_foundation_preserves_sources_and_historical_matching(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_08_27_180000_create_fuel_transaction_import_foundation.php'));
        $service = file_get_contents(app_path('Modules/Fuel/Services/FuelTransactionImportService.php'));
        $routes = file_get_contents(app_path('Modules/Fuel/Routes/api.php'));

        self::assertIsString($migration);
        self::assertIsString($service);
        self::assertIsString($routes);

        foreach (['fuel_import_batches', 'fuel_import_rows', 'fuel_transactions', 'raw_payload', 'normalized_payload', 'transaction_fingerprint'] as $marker) {
            self::assertStringContainsString($marker, $migration);
        }
        foreach (['ORLEN', 'MOL', 'Číslo účtenky', 'Číslo stvrzenky', 'provider_card_and_assignment_period', 'no_valid_assignment', 'unknown_card', 'file_sha256'] as $marker) {
            self::assertStringContainsString($marker, $service);
        }
        foreach (['fuel-imports', "'index'", "'show'", "'store'"] as $marker) {
            self::assertStringContainsString($marker, $routes);
        }
    }
}
