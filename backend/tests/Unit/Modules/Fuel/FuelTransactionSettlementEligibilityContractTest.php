<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fuel;

use Tests\TestCase;

final class FuelTransactionSettlementEligibilityContractTest extends TestCase
{
    public function test_settlement_eligibility_is_revisioned_append_only_and_separate_from_pricing(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_09_04_180000_create_fuel_transaction_settlement_eligibility_foundation.php'));
        $service = file_get_contents(app_path('Modules/Fuel/Services/FuelTransactionSettlementEligibilityService.php'));
        $routes = file_get_contents(app_path('Modules/Fuel/Routes/api.php'));
        foreach ([$migration, $service, $routes] as $source) {
            self::assertIsString($source);
        }
        foreach (['fuel_transaction_settlement_eligibilities', 'fuel_transaction_settlement_eligibility_evaluations', 'reconciliation_revision', 'fuel_card_settlement_policy_id', 'base_amount', 'revision'] as $marker) {
            self::assertStringContainsString($marker, $migration);
        }
        foreach (['reconciliation_not_resolved', 'settlement_policy_missing', 'settlement_policy_ambiguous', 'settlement_target_missing', 'settlement_amount_missing', 'expected_revision'] as $marker) {
            self::assertStringContainsString($marker, $service);
        }
        self::assertStringNotContainsString('FinancialCalculation', $service);
        self::assertStringContainsString("name('settlement-eligibility.show')", $routes);
        self::assertStringContainsString("name('settlement-eligibility.evaluate')", $routes);
    }
}
