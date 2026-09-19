<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Tests\TestCase;

final class FinancialSettlementAccountingPostingAdministrationFoundationTest extends TestCase
{
    public function test_read_only_administration_api_and_workspace_contract_are_installed(): void
    {
        $backend = dirname(__DIR__, 4);
        $routes = file_get_contents($backend.'/app/Modules/Pricing/Routes/api.php');
        $workspace = file_get_contents($backend.'/resources/views/mvp/financial-settlement-statements.blade.php');
        $partial = file_get_contents($backend.'/resources/views/mvp/partials/financial-settlement-accounting-posting-administration.blade.php');

        self::assertIsString($routes);
        self::assertIsString($workspace);
        self::assertIsString($partial);
        self::assertStringContainsString('financial-settlement-accounting-postings', $routes);
        self::assertStringContainsString('compensation.view', $routes);
        self::assertStringContainsString('mvp.partials.financial-settlement-accounting-posting-administration', $workspace);
        self::assertStringContainsString('data-accounting-posting-administration', $partial);
        self::assertStringContainsString('request(`${endpoint}', $partial);
        self::assertStringNotContainsString("method: 'POST'", $partial);
        self::assertStringNotContainsString("method: 'PATCH'", $partial);
        self::assertStringNotContainsString("method: 'DELETE'", $partial);
    }
}
