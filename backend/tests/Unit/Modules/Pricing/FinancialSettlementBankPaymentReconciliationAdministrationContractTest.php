<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class FinancialSettlementBankPaymentReconciliationAdministrationContractTest extends TestCase
{
    public function test_administration_exposes_scoped_reconciliation_and_reuses_existing_commands(): void
    {
        $root = dirname(__DIR__, 4);
        $readService = file_get_contents($root.'/app/Modules/Pricing/Services/FinancialSettlementAdministrationReadService.php');
        $view = file_get_contents($root.'/resources/views/mvp/financial-settlement-statements.blade.php');
        $routes = file_get_contents($root.'/app/Modules/Pricing/Routes/api.php');
        self::assertIsString($readService);
        self::assertIsString($view);
        self::assertIsString($routes);

        foreach (['FinancialSettlementBankPaymentReconciliation', "with('events')", "where('owner_organization_id'", "'reconciliation'", "'payment_revision'", "'confirmed_at'", "'reopened_at'"] as $marker) {
            self::assertStringContainsString($marker, $readService);
        }
        foreach (['data-confirm-reconciliation', 'data-reopen-reconciliation', 'expected_payment_revision', 'expected_reconciliation_revision', '/reconciliation/confirm', '/reconciliation/reopen', 'reconciliation_confirmed', 'reconciliation_reopened'] as $marker) {
            self::assertStringContainsString($marker, $view);
            self::assertStringContainsString($marker, $view.$routes);
        }
        self::assertStringNotContainsString('FinancialSettlementBankPayment::query()->update', $readService);
        self::assertStringNotContainsString('FinancialSettlementStatement::query()->update', $readService);
        self::assertStringNotContainsString('BillingDocument::query()->update', $readService);
    }
}
