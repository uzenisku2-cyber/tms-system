<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class FinancialSettlementBankPaymentAdministrationContractTest extends TestCase
{
    public function test_administration_is_scoped_dynamic_audited_and_uses_existing_lifecycle_commands(): void
    {
        $root = dirname(__DIR__, 4);
        $readService = file_get_contents($root.'/app/Modules/Pricing/Services/FinancialSettlementAdministrationReadService.php');
        $controller = file_get_contents($root.'/app/Modules/Pricing/Controllers/FinancialSettlementBankMatchCandidateController.php');
        $routes = file_get_contents($root.'/app/Modules/Pricing/Routes/api.php');
        $view = file_get_contents($root.'/resources/views/mvp/financial-settlement-statements.blade.php');
        self::assertIsString($readService);
        self::assertIsString($controller);
        self::assertIsString($routes);
        self::assertIsString($view);

        foreach (['settlement_payment_state', 'settlement_paid_amount_minor', 'settlement_remaining_amount_minor', "'unpaid'", "'partially_paid'", "'paid'", 'bank_match_candidates', 'bank_payments', 'can_manage_settlement_bank_payments'] as $marker) {
            self::assertStringContainsString($marker, $readService);
        }
        foreach (['ProposeFinancialSettlementBankMatchCandidateRequest', "'propose'", 'bank-match-candidates', 'bank-payments/{payment}/reverse'] as $marker) {
            self::assertStringContainsString($marker, $controller.$routes);
        }
        foreach (['expected_revision', 'expected_candidate_revision', 'idempotency_key', 'data-candidate-decision', 'data-materialize', 'data-reverse-payment'] as $marker) {
            self::assertStringContainsString($marker, $view);
        }
        self::assertStringContainsString("'bank_matching_performed' => false", $readService);
        self::assertStringNotContainsString('VehicleCostAllocationBankMatchingExecution::query()', $readService.$controller.$view);
        self::assertStringNotContainsString('BillingDocument::query()->update', $readService.$controller.$view);
        self::assertStringNotContainsString('FinancialSettlementStatement::query()->update', $readService.$controller.$view);
    }

    public function test_administration_list_presents_payment_state_and_business_labels(): void
    {
        $source = file_get_contents(resource_path('views/mvp/financial-settlement-statements.blade.php'));

        self::assertIsString($source);
        self::assertStringContainsString('settlement_payment_state', $source);
        self::assertStringContainsString('settlement_paid_amount_minor', $source);
        self::assertStringContainsString('settlement_remaining_amount_minor', $source);
        self::assertStringContainsString('outputLabel(item.output_kind)', $source);
        self::assertStringContainsString('Zb&#253;v&#225; uhradit', $source);
        self::assertStringContainsString('vy\\u00fa\\u010dtov\\u00e1n\\u00ed', $source);
        self::assertStringContainsString('\\u010c\\u00e1ste\\u010dn\\u011b uhrazeno', $source);
        self::assertStringContainsString('compactId', $source);
        self::assertStringNotContainsString('item.payment_state', $source);
        self::assertStringNotContainsString('item.paid_amount_minor', $source);
        self::assertStringNotContainsString('item.remaining_amount_minor', $source);
    }
}
