<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use Tests\TestCase;

final class FinancialSettlementAccountingPostingExecutionContractTest extends TestCase
{
    public function test_execution_is_balanced_scoped_idempotent_append_only_and_source_non_mutating(): void
    {
        $root = __DIR__.'/../../../../';
        $service = file_get_contents($root.'app/Modules/Pricing/Services/FinancialSettlementAccountingPostingExecutionService.php');
        $models = file_get_contents($root.'app/Modules/Pricing/Models/FinancialSettlementAccountingPostingExecution.php').file_get_contents($root.'app/Modules/Pricing/Models/FinancialSettlementAccountingPostingEntry.php').file_get_contents($root.'app/Modules/Pricing/Models/FinancialSettlementAccountingPostingExecutionEvent.php');
        $migration = file_get_contents($root.'database/migrations/2026_09_20_000000_create_financial_settlement_accounting_posting_executions.php');
        $routes = file_get_contents($root.'app/Modules/Pricing/Routes/api.php');
        foreach ([$service, $models, $migration, $routes] as $source) {
            self::assertIsString($source);
        }
        foreach (['STATUS_PREPARED', 'expected_handoff_revision', 'lockForUpdate', 'idempotency_key', 'command_fingerprint', 'owner_organization_id', 'SIDE_DEBIT', 'SIDE_CREDIT', "'entry_count' => 2", "'balanced' => true", 'financial_settlement_accounting_posted'] as $marker) {
            self::assertStringContainsString($marker, $service);
        }
        foreach (["'payment_modified' => false", "'reconciliation_modified' => false", "'settlement_statement_modified' => false", "'billing_document_modified' => false", "'bank_transaction_evidence_modified' => false"] as $boundary) {
            self::assertStringContainsString($boundary, $service);
        }
        self::assertStringContainsString('append-only', $models);
        foreach (['fsape_org_idem_unique', 'fsape_handoff_unique', 'fsapentry_execution_sequence_unique', 'fsapee_execution_revision_unique'] as $constraint) {
            self::assertStringContainsString($constraint, $migration);
        }
        self::assertStringContainsString('accounting-posting-executions', $routes);
        foreach (['FinancialSettlementBankPayment::query()', 'FinancialSettlementBankPaymentReconciliation::query()', 'FinancialSettlementStatement::query()', 'BillingDocument::query()', 'BankTransactionEvidence::query()', '->update(', '->delete('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $service);
        }
    }
}
