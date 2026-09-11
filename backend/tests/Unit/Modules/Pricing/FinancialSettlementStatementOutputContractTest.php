<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\FinancialSettlementStatement;
use PHPUnit\Framework\TestCase;

final class FinancialSettlementStatementOutputContractTest extends TestCase
{
    public function test_settlement_output_kinds_map_to_existing_billing_document_types(): void
    {
        self::assertSame('external_carrier_settlement', BillingDocument::TYPE_EXTERNAL_CARRIER_SETTLEMENT);
        self::assertSame('driver_remuneration', BillingDocument::TYPE_DRIVER_REMUNERATION);
        self::assertSame('carrier_payable', FinancialSettlementStatement::OUTPUT_CARRIER_PAYABLE);
        self::assertSame('carrier_receivable', FinancialSettlementStatement::OUTPUT_CARRIER_RECEIVABLE);
        self::assertSame('driver_payout', FinancialSettlementStatement::OUTPUT_DRIVER_PAYOUT);
        self::assertSame('driver_deduction', FinancialSettlementStatement::OUTPUT_DRIVER_DEDUCTION);
        self::assertSame('zero_balance', FinancialSettlementStatement::OUTPUT_ZERO_BALANCE);
    }

    public function test_foundation_declares_linkage_without_creating_financial_side_effects(): void
    {
        $source = file_get_contents(__DIR__.'/../../../../app/Modules/Pricing/Models/FinancialSettlementStatement.php');
        self::assertIsString($source);
        foreach (['billing_document_id', 'output_kind', 'output_direction', 'output_materialized_at', 'billingDocument'] as $marker) {
            self::assertStringContainsString($marker, $source);
        }
        self::assertStringNotContainsString('BillingDocument::query()->create', $source);
        self::assertStringNotContainsString('BankTransaction', $source);
        self::assertStringNotContainsString('paid_at', $source);
    }
}
