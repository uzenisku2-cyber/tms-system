<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FinancialSettlementAccountingPeriodCloseReadinessContractTest extends TestCase
{
    #[Test]
    public function close_readiness_preserves_accounting_boundaries(): void
    {
        $root = dirname(__DIR__, 4);
        $readiness = file_get_contents($root.'/app/Modules/Pricing/Services/FinancialSettlementAccountingPeriodCloseReadinessService.php');
        $periods = file_get_contents($root.'/app/Modules/Pricing/Services/FinancialSettlementAccountingPeriodService.php');
        $routes = file_get_contents($root.'/app/Modules/Pricing/Routes/api.php');

        self::assertIsString($readiness);
        self::assertIsString($periods);
        self::assertIsString($routes);
        foreach (['owner_organization_id', 'posting_date', 'currency', 'pending_accounting_posting_handoffs', 'unbalanced_accounting_posting_executions', 'lockForUpdate'] as $marker) {
            self::assertStringContainsString($marker, $readiness);
        }
        self::assertStringContainsString("abort_unless((bool) \$readiness['ready'], 409", $periods);
        self::assertStringContainsString('close_readiness', $periods);
        self::assertStringContainsString('close-readiness', $routes);
        foreach (['FinancialSettlementAccountingPostingReversal::query()->update', 'FinancialSettlementAccountingPostingCorrection::query()->update', 'FinancialSettlementBankPayment::query()->update', 'BillingDocument::query()->update'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $readiness.$periods);
        }
    }
}
