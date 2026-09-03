<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fuel;

use Tests\TestCase;

final class FuelTransactionAdministrationContractTest extends TestCase
{
    public function test_overview_contract_is_scoped_filterable_and_business_friendly(): void
    {
        $service = file_get_contents(app_path('Modules/Fuel/Services/FuelTransactionAdministrationService.php'));
        $request = file_get_contents(app_path('Modules/Fuel/Requests/IndexFuelTransactionRequest.php'));
        $routes = file_get_contents(app_path('Modules/Fuel/Routes/api.php'));
        $model = file_get_contents(app_path('Modules/Fuel/Models/FuelTransaction.php'));
        $controller = file_get_contents(app_path('Modules/Fuel/Controllers/FuelTransactionController.php'));
        $export = file_get_contents(app_path('Modules/Fuel/Services/FuelTransactionCsvExportService.php'));

        self::assertIsString($service);
        self::assertIsString($request);
        self::assertIsString($routes);
        self::assertIsString($model);
        self::assertIsString($controller);
        self::assertIsString($export);
        foreach (['owner_organization_id', 'date_from', 'date_to', 'provider', 'driver_id', 'card', 'search', 'reconciliation_status', 'masked_card', 'effective_driver', 'reconciliation', 'candidate_count', 'pagination'] as $marker) {
            self::assertStringContainsString($marker, $service.$request);
        }
        foreach (['function overview(', 'attention_required', 'currency_totals', 'statusCount', 'baseQuery', 'COALESCE(actual_driver_id, driver_id)'] as $marker) {
            self::assertStringContainsString($marker, $service);
        }
        self::assertStringContainsString("Route::get('/overview', [FuelTransactionController::class, 'overview'])->name('overview')", $routes);
        self::assertStringContainsString("Route::get('/', [FuelTransactionController::class, 'index'])", $routes);
        self::assertStringContainsString("middleware('perm:compensation.view')", $routes);
        self::assertStringContainsString('function reconciliation(): HasOne', $model);
        self::assertStringNotContainsString("'raw_payload' =>", $service);
        self::assertStringNotContainsString("'normalized_payload' =>", $service);
        self::assertStringNotContainsString("'provider_card_identifier' =>", $service);
        self::assertStringNotContainsString("forceFill(['quantity'", $service);
        self::assertStringNotContainsString("forceFill(['gross_amount'", $service);
        self::assertStringContainsString('function exportRows(', $service);
        self::assertStringContainsString('->lazy(500)', $service);
        self::assertStringContainsString("Route::get('/export', [FuelTransactionController::class, 'export'])->name('export')", $routes);
        self::assertStringContainsString('streamDownload', $controller);
        self::assertStringContainsString('text/csv; charset=UTF-8', $controller);
        foreach (['\xEF\xBB\xBF', 'fputcsv', "';'", 'masked_card', 'effective_driver'] as $marker) {
            self::assertStringContainsString($marker, $export);
        }
        foreach (['raw_payload', 'normalized_payload', 'provider_card_identifier'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $export);
        }
    }
}
