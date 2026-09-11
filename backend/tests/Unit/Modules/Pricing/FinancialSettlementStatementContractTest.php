<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Modules\Pricing\Models\FinancialSettlementStatement;
use App\Modules\Pricing\Models\FinancialSettlementStatementLine;
use PHPUnit\Framework\TestCase;

final class FinancialSettlementStatementContractTest extends TestCase
{
    public function test_statement_separates_earnings_deductions_and_signed_net_balance(): void
    {
        self::assertSame(['organization', 'driver'], FinancialSettlementStatement::RECIPIENT_TYPES);
        self::assertSame(['financial_calculation', 'financial_mutual_charge'], FinancialSettlementStatementLine::SOURCE_TYPES);
        self::assertSame(['earning', 'deduction'], FinancialSettlementStatementLine::EFFECTS);

        $model = file_get_contents(__DIR__.'/../../../../app/Modules/Pricing/Models/FinancialSettlementStatement.php');
        $line = file_get_contents(__DIR__.'/../../../../app/Modules/Pricing/Models/FinancialSettlementStatementLine.php');
        $migration = file_get_contents(__DIR__.'/../../../../database/migrations/2026_09_14_120000_create_financial_settlement_statement_foundation.php');
        self::assertIsString($model);
        self::assertIsString($line);
        self::assertIsString($migration);
        foreach (['earning_amount_minor', 'deduction_amount_minor', 'net_balance_minor'] as $marker) {
            self::assertStringContainsString($marker, $model);
        }
        self::assertStringContainsString("\$table->bigInteger('net_balance_minor')", $migration);
        self::assertStringContainsString('net_balance_minor = earning_amount_minor - deduction_amount_minor', $migration);
        self::assertStringContainsString('source_public_id', $line);
        self::assertStringContainsString('source_snapshot', $line);
        self::assertStringContainsString('append-only', $line);
        self::assertStringNotContainsString('BillingDocument::query()', $model.$line);
        self::assertStringNotContainsString('BankTransaction', $model.$line);
        self::assertStringNotContainsString('payment_marked', $model.$line);
    }
}
