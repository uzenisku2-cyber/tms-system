<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fuel;

use PHPUnit\Framework\TestCase;

final class FuelTransactionSettlementApplicationContractTest extends TestCase
{
    public function test_application_is_unique_revisioned_append_only_and_separate_from_calculation(): void
    {
        $migration = file_get_contents(__DIR__.'/../../../../database/migrations/2026_09_04_210000_create_fuel_transaction_settlement_applications.php');
        $integrationMigration = file_get_contents(__DIR__.'/../../../../database/migrations/2026_09_05_000000_allow_financial_calculation_attached_settlement_events.php');
        $service = file_get_contents(__DIR__.'/../../../../app/Modules/Fuel/Services/FuelTransactionSettlementApplicationService.php');
        $routes = file_get_contents(__DIR__.'/../../../../app/Modules/Fuel/Routes/api.php');
        self::assertIsString($migration);
        self::assertIsString($integrationMigration);
        self::assertIsString($service);
        self::assertIsString($routes);
        foreach (['fuel_transaction_settlement_applications', 'fuel_transaction_settlement_application_events', "foreignId('fuel_transaction_id')->unique()", 'eligibility_revision', 'reconciliation_revision', 'financial_calculation_id', 'fuel_settlement_app_event_revision_unique'] as $marker) {
            self::assertStringContainsString($marker, $migration);
        }
        foreach (['settlement_application', 'expected_eligibility_revision', 'expected_revision', 'STATUS_RESOLVED', 'STATUS_ELIGIBLE', 'lockForUpdate', 'TYPE_APPLIED', 'TYPE_REVERSED', 'TYPE_FINANCIAL_CALCULATION_ATTACHED', 'FinancialCalculation', 'performed_by_driver_id'] as $marker) {
            self::assertStringContainsString($marker, $service);
        }
        self::assertStringNotContainsString('FinancialCalculation::query()->create', $service);
        self::assertStringContainsString('financial_calculation_attached', $integrationMigration);
        foreach (['settlement-application.show', 'settlement-application.apply', 'settlement-application.reverse', 'settlement-application.financial-calculation.attach'] as $marker) {
            self::assertStringContainsString($marker, $routes);
        }
    }
}
