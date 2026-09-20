<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Tests\TestCase;

final class FinancialSettlementAccountingPeriodAdministrationFoundationTest extends TestCase
{
    public function test_accounting_period_administration_api_and_workspace_contract_are_installed(): void
    {
        $backend = dirname(__DIR__, 4);
        $routes = file_get_contents($backend.'/app/Modules/Pricing/Routes/api.php');
        $workspace = file_get_contents($backend.'/resources/views/mvp/financial-settlement-statements.blade.php');
        $partial = file_get_contents($backend.'/resources/views/mvp/partials/financial-settlement-accounting-period-administration.blade.php');

        self::assertIsString($routes);
        self::assertIsString($workspace);
        self::assertIsString($partial);
        self::assertStringContainsString('FinancialSettlementAccountingPeriodAdministrationReadController', $routes);
        self::assertStringContainsString("Route::get('financial-settlement-accounting-periods'", $routes);
        self::assertStringContainsString("Route::get('financial-settlement-accounting-periods/{accountingPeriod}'", $routes);
        self::assertStringContainsString('compensation.view', $routes);
        self::assertStringContainsString('mvp.partials.financial-settlement-accounting-period-administration', $workspace);
        self::assertStringContainsString('data-accounting-period-administration', $partial);
        self::assertStringContainsString('expected_revision', $partial);
        self::assertStringContainsString('idempotency_key', $partial);
    }
}
