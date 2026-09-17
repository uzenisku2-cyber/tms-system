<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class FinancialSettlementBankPaymentReconciliationContractTest extends TestCase
{
    public function test_reconciliation_is_scoped_optimistic_idempotent_audited_and_non_mutating(): void
    {
        $root = dirname(__DIR__, 4);
        $service = file_get_contents($root.'/app/Modules/Pricing/Services/FinancialSettlementBankPaymentReconciliationService.php');
        $event = file_get_contents($root.'/app/Modules/Pricing/Models/FinancialSettlementBankPaymentReconciliationEvent.php');
        $routes = file_get_contents($root.'/app/Modules/Pricing/Routes/api.php');
        self::assertIsString($service);
        self::assertIsString($event);
        self::assertIsString($routes);

        foreach (['expected_payment_revision', 'expected_reconciliation_revision', 'expected_revision', 'lockForUpdate', 'idempotency_key', 'command_fingerprint', 'STATUS_CONFIRMED', 'STATUS_OPEN', 'reconciliation_confirmed', 'reconciliation_reopened'] as $marker) {
            self::assertStringContainsString($marker, $service);
        }
        self::assertStringContainsString('events are append-only', $event);
        self::assertStringContainsString('bank-payments/{payment}/reconciliation/confirm', $routes);
        self::assertStringContainsString('bank-payments/{payment}/reconciliation/reopen', $routes);
        foreach (["'payment_modified' => false", "'settlement_statement_modified' => false", "'billing_document_modified' => false", "'bank_transaction_evidence_modified' => false", "'accounting_entry_created' => false"] as $boundary) {
            self::assertStringContainsString($boundary, $service);
        }
        foreach (['FinancialSettlementBankPayment::query()->update', 'FinancialSettlementStatement::query()->update', 'BillingDocument::query()->update', 'BankTransactionEvidence::query()->update'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $service);
        }
    }
}
