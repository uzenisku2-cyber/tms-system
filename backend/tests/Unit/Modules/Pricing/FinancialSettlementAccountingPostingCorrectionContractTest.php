<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FinancialSettlementAccountingPostingCorrectionContractTest extends TestCase
{
    #[Test]
    public function correction_is_scoped_idempotent_append_only_balanced_and_non_mutating(): void
    {
        $root = dirname(__DIR__, 4);
        $service = file_get_contents($root.'/app/Modules/Pricing/Services/FinancialSettlementAccountingPostingCorrectionService.php');
        $model = file_get_contents($root.'/app/Modules/Pricing/Models/FinancialSettlementAccountingPostingCorrection.php');
        $route = file_get_contents($root.'/app/Modules/Pricing/Routes/api.php');
        self::assertIsString($service);
        self::assertIsString($model);
        self::assertIsString($route);
        foreach (['owner_organization_id', 'idempotency_key', 'command_fingerprint', 'expected_revision', 'original_execution_id', 'reversal_id', 'replacement_execution_id', "where('side', 'debit')", "where('side', 'credit')"] as $marker) {
            self::assertStringContainsString($marker, $service.$model);
        }
        self::assertStringContainsString('append-only', $model);
        self::assertStringContainsString("'financial_settlement_accounting_posting_handoff_id' => null", $service);
        self::assertStringContainsString('compensation.manage', $route);
        foreach (['Payment::query()', 'BillingDocument::query()', 'FinancialSettlementBankPaymentReconciliation::query()', 'BankTransactionEvidence::query()'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $service);
        }
    }
}
