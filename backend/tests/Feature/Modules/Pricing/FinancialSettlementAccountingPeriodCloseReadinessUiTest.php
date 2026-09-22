<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Tests\TestCase;

final class FinancialSettlementAccountingPeriodCloseReadinessUiTest extends TestCase
{
    public function test_workspace_exposes_readiness_summary_blockers_refresh_and_guarded_close_confirmation(): void
    {
        $backend = dirname(__DIR__, 4);
        $workspace = file_get_contents($backend.'/resources/views/mvp/financial-settlement-statements.blade.php');
        $partial = file_get_contents($backend.'/resources/views/mvp/partials/financial-settlement-accounting-period-administration.blade.php');

        self::assertIsString($workspace);
        self::assertIsString($partial);
        self::assertStringContainsString('mvp.partials.financial-settlement-accounting-period-administration', $workspace);
        self::assertStringContainsString('data-accounting-period-administration', $partial);
        self::assertStringContainsString('data-refresh-close-readiness', $partial);
        self::assertStringContainsString('data-confirm-period-close', $partial);
        self::assertStringContainsString('pending_accounting_posting_handoffs', $partial);
        self::assertStringContainsString('unbalanced_accounting_posting_executions', $partial);
        self::assertStringContainsString('blocker.public_ids', $partial);
        self::assertStringContainsString('readiness.period_revision', $partial);
    }
}
