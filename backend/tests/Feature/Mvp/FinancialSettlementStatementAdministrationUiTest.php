<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class FinancialSettlementStatementAdministrationUiTest extends TestCase
{
    public function test_settlement_statement_administration_exposes_exact_lifecycle_and_output_controls(): void
    {
        $this->get('/settings/financial-settlement-statements')
            ->assertOk()
            ->assertSee('Vy&#250;&#269;tov&#225;n&#237; &#345;idi&#269;&#367; a dopravc&#367;', false)
            ->assertSee('/api/v1/financial-settlement-statements?', false)
            ->assertSee('data.items', false)
            ->assertSee('earning_amount_minor', false)
            ->assertSee('deduction_amount_minor', false)
            ->assertSee('net_balance_minor', false)
            ->assertSee('billing_document', false)
            ->assertSee('id="lineRows"', false)
            ->assertSee('event.event_type', false)
            ->assertSee('id="lifecycleActions"', false)
            ->assertSee("actions.push(['submit'", false)
            ->assertSee("actions.push(['approve'", false)
            ->assertSee("actions.push(['close'", false)
            ->assertSee("actions.push(['cancel'", false)
            ->assertSee('expected_revision', false)
            ->assertSee('crypto.randomUUID()', false)
            ->assertSee("under_review:'Ke kontrole'", false)
            ->assertSee('id="materializationPanel"', false)
            ->assertSee('id="carrierDocumentFields"', false)
            ->assertSee('/materialize-output', false)
            ->assertSee('document_number', false)
            ->assertSee('counterparty_name', false)
            ->assertSee('net_amount', false)
            ->assertSee('vat_amount', false)
            ->assertSee('vat_rate_basis_points', false)
            ->assertSee('expected_revision', false);

        $source = file_get_contents(resource_path('views/mvp/financial-settlement-statements.blade.php'));
        self::assertIsString($source);
        self::assertStringNotContainsString('Obrazovka je pouze pro &#269;ten&#237;.', $source);

        $readService = file_get_contents(app_path('Modules/Pricing/Services/FinancialSettlementAdministrationReadService.php'));
        self::assertIsString($readService);
        self::assertStringContainsString("where('output_direction', \$filters['direction'])", $readService);

        foreach ([
            'id="bankPaymentAdministration"', 'id="paymentSummary"', 'settlement_payment_state',
            'settlement_paid_amount_minor', 'settlement_remaining_amount_minor', 'id="proposeMatch"',
            'id="candidateRows"', 'score_basis_points', 'match_reasons', 'data-candidate-decision',
            'data-materialize', 'id="paymentRows"', 'data-reverse-payment', '/bank-match-candidates',
            '/review', '/materialize', '/bank-payments/', '/reverse',
            'reconciliation', 'data-confirm-reconciliation', 'data-reopen-reconciliation',
            '/reconciliation/confirm', '/reconciliation/reopen', 'expected_payment_revision',
            'expected_reconciliation_revision', 'reconciliation_confirmed', 'reconciliation_reopened',
        ] as $marker) {
            self::assertStringContainsString($marker, $source);
        }
        self::assertStringContainsString("'bank_matching_performed' => false", $readService);
    }

    public function test_settings_links_to_settlement_statement_administration(): void
    {
        $this->get('/settings')
            ->assertOk()
            ->assertSee('/settings/financial-settlement-statements', false)
            ->assertSee('settings-financial-settlement-statements', false);
    }
}
