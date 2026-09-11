<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class FinancialSettlementStatementDraftContractTest extends TestCase
{
    public function test_draft_service_requires_approved_calculations_and_confirmed_mutual_charges(): void
    {
        $source = file_get_contents(__DIR__.'/../../../../app/Modules/Pricing/Services/FinancialSettlementStatementService.php');
        self::assertIsString($source);
        foreach (['STATUS_APPROVED', 'STATUS_CONFIRMED', 'performed_by_driver_id', 'service_date', 'offset_eligible', 'lockForUpdate', 'minor(', 'net_balance_minor', 'source_snapshot'] as $marker) {
            self::assertStringContainsString($marker, $source);
        }
        $materializationOffset = strpos($source, 'public function materialize');
        self::assertNotFalse($materializationOffset);
        $draftServiceSource = substr($source, 0, (int) $materializationOffset);
        $materializationServiceSource = substr($source, (int) $materializationOffset);
        self::assertStringNotContainsString('BillingDocument::query()->create', $draftServiceSource);
        self::assertStringContainsString('BillingDocument::query()->create', $materializationServiceSource);
        foreach (['BankTransactionEvidence::query()->create', 'payment_marked'."' => true"] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }
}
