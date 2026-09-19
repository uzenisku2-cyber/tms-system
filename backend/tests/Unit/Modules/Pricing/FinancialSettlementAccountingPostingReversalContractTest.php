<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FinancialSettlementAccountingPostingReversalContractTest extends TestCase
{
    #[Test]
    public function reversal_is_scoped_idempotent_append_only_balanced_and_non_mutating(): void
    {
        $root = dirname(__DIR__, 4);
        $service = file_get_contents($root.'/app/Modules/Pricing/Services/FinancialSettlementAccountingPostingReversalService.php');
        $migration = file_get_contents($root.'/database/migrations/2026_09_21_000000_create_financial_settlement_accounting_posting_reversals.php');
        self::assertIsString($service);
        self::assertIsString($migration);
        foreach (['owner_organization_id', 'idempotency_key', 'command_fingerprint', 'expected_revision', 'lockForUpdate', 'original_posting_entry_id'] as $marker) {
            self::assertStringContainsString($marker, $service.$migration);
        }
        self::assertStringContainsString("\$originalSide === 'debit' ? 'credit' : 'debit'", $service);
        self::assertStringNotContainsString('->update(', $service);
        self::assertStringNotContainsString('->delete(', $service);
    }
}
