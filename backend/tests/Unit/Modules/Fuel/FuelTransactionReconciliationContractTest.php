<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fuel;

use Tests\TestCase;

final class FuelTransactionReconciliationContractTest extends TestCase
{
    public function test_reconciliation_separates_import_matching_evaluation_and_manual_decision(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_09_01_210000_create_fuel_transaction_reconciliation_foundation.php'));
        $service = file_get_contents(app_path('Modules/Fuel/Services/FuelTransactionReconciliationService.php'));
        $routes = file_get_contents(app_path('Modules/Fuel/Routes/api.php'));
        self::assertIsString($migration);
        self::assertIsString($service);
        self::assertIsString($routes);
        foreach (['fuel_transaction_reconciliations', 'fuel_transaction_reconciliation_evaluations', 'fuel_transaction_reconciliation_decisions', 'expected_revision', 'evaluation_version', 'decision_code'] as $marker) {
            self::assertStringContainsString($marker, $migration.$service);
        }
        foreach (['import_requires_review', 'missing_effective_driver', 'missing_driver_organization_assignment', 'no_operational_activity', 'driver_day_matched', 'vehicle_matched', 'vehicle_mismatch', 'vehicle_unconfirmed'] as $code) {
            self::assertStringContainsString($code, $service);
        }
        self::assertStringContainsString('self::PERMISSION', $service);
        self::assertStringContainsString('visibleDriverOrganizationAssignmentIds', $service);
        self::assertStringContainsString('reconciliation/evaluate', $routes);
        self::assertStringContainsString('reconciliation/decisions', $routes);
        self::assertStringNotContainsString("forceFill(['match_status'", $service);
        self::assertStringNotContainsString("forceFill(['quantity'", $service);
        self::assertStringNotContainsString("forceFill(['gross_amount'", $service);
    }
}
