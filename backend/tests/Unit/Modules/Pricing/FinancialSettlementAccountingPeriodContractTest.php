<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class FinancialSettlementAccountingPeriodContractTest extends TestCase
{
    public function test_period_close_contract_is_scoped_idempotent_audited_and_allows_compensating_history(): void
    {
        $root = dirname(__DIR__, 4);
        $service = file_get_contents($root.'/app/Modules/Pricing/Services/FinancialSettlementAccountingPeriodService.php');
        $execution = file_get_contents($root.'/app/Modules/Pricing/Models/FinancialSettlementAccountingPostingExecution.php');
        self::assertIsString($service);
        self::assertIsString($execution);
        foreach (['owner_organization_id', 'idempotency_key', 'command_fingerprint', 'expected_revision', 'lockForUpdate', 'accounting_period_closed', 'accounting_period_reopened'] as $marker) {
            self::assertStringContainsString($marker, $service);
        }
        self::assertStringContainsString('financial_settlement_accounting_posting_handoff_id', $execution);
        self::assertStringContainsString('FinancialSettlementAccountingPeriod::STATUS_CLOSED', $execution);
        self::assertStringNotContainsString('FinancialSettlementAccountingPostingReversal::query()->update', $service);
        self::assertStringNotContainsString('FinancialSettlementAccountingPostingCorrection::query()->update', $service);
    }
}
