<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class FinancialSettlementAccountingPeriodCloseReadinessUiContractTest extends TestCase
{
    public function test_close_action_requires_a_fresh_readiness_projection_and_preserves_command_contract(): void
    {
        $backend = dirname(__DIR__, 4);
        $partial = file_get_contents($backend.'/resources/views/mvp/partials/financial-settlement-accounting-period-administration.blade.php');

        self::assertIsString($partial);
        self::assertStringContainsString('/close-readiness', $partial);
        self::assertStringContainsString('readiness.ready', $partial);
        self::assertStringContainsString('readiness.blockers', $partial);
        self::assertStringContainsString('pending_handoff_count', $partial);
        self::assertStringContainsString('unbalanced_posting_execution_count', $partial);
        self::assertStringContainsString('data-confirm-period-close', $partial);
        self::assertStringContainsString('expected_revision:Number(revision)', $partial);
        self::assertStringContainsString('idempotency_key:crypto.randomUUID()', $partial);
    }
}
