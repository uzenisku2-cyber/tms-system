<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use Tests\TestCase;

final class FinancialSettlementAccountingPostingHandoffContractTest extends TestCase
{
    public function test_handoff_is_confirmed_scoped_idempotent_append_only_and_non_posting(): void
    {
        $root = __DIR__.'/../../../../';
        $service = file_get_contents($root.'app/Modules/Pricing/Services/FinancialSettlementAccountingPostingHandoffService.php');
        $model = file_get_contents($root.'app/Modules/Pricing/Models/FinancialSettlementAccountingPostingHandoff.php');
        $event = file_get_contents($root.'app/Modules/Pricing/Models/FinancialSettlementAccountingPostingHandoffEvent.php');
        $migration = file_get_contents($root.'database/migrations/2026_09_19_000000_create_financial_settlement_accounting_posting_handoffs.php');
        $routes = file_get_contents($root.'app/Modules/Pricing/Routes/api.php');
        foreach ([$service, $model, $event, $migration, $routes] as $source) {
            self::assertIsString($source);
        }
        foreach ([
            'STATUS_CONFIRMED', 'STATUS_ACTIVE', 'expected_payment_revision', 'expected_reconciliation_revision',
            'lockForUpdate', 'idempotency_key', 'command_fingerprint', 'owner_organization_id',
            'source_snapshot', 'accounting_posting_handoff_prepared',
        ] as $marker) {
            self::assertStringContainsString($marker, $service);
        }
        foreach ([
            "'accounting_posting_performed' => false", "'ledger_entry_created' => false",
            "'payment_modified' => false", "'reconciliation_modified' => false",
            "'settlement_statement_modified' => false", "'billing_document_modified' => false",
            "'bank_transaction_evidence_modified' => false",
        ] as $boundary) {
            self::assertStringContainsString($boundary, $service);
        }
        self::assertStringContainsString('append-only', $model.$event);
        foreach (['fsaph_org_idem_unique', 'fsaph_reconciliation_revision_unique', 'fsaphe_handoff_revision_unique'] as $constraint) {
            self::assertStringContainsString($constraint, $migration);
        }
        self::assertStringContainsString('accounting-handoff', $routes);
        self::assertStringNotContainsString('Ledger::query()', $service);
        self::assertStringNotContainsString('AccountingEntry::query()', $service);
        self::assertStringNotContainsString('->update(', $service);
        self::assertStringNotContainsString('->delete(', $service);
    }
}
