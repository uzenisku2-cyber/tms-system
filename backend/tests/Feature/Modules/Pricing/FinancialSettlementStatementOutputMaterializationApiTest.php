<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FinancialSettlementStatementOutputMaterializationApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function source_contract_materializes_all_output_kinds_without_payment_or_bank_execution(): void
    {
        $service = file_get_contents(__DIR__.'/../../../../app/Modules/Pricing/Services/FinancialSettlementStatementService.php');
        self::assertIsString($service);
        foreach ([
            'OUTPUT_CARRIER_PAYABLE', 'OUTPUT_CARRIER_RECEIVABLE',
            'OUTPUT_DRIVER_PAYOUT', 'OUTPUT_DRIVER_DEDUCTION', 'OUTPUT_ZERO_BALANCE',
            'TYPE_EXTERNAL_CARRIER_SETTLEMENT', 'TYPE_DRIVER_REMUNERATION',
            'lockForUpdate', 'expected_revision', 'output_materialized', 'billing_document_id',
        ] as $marker) {
            self::assertStringContainsString($marker, $service);
        }
        self::assertStringContainsString("'payment_marked' => false", $service);
        self::assertStringContainsString("'bank_matching_performed' => false", $service);
        self::assertStringNotContainsString("'payment_marked' => true", $service);
        self::assertStringNotContainsString("'bank_matching_performed' => true", $service);
    }
}
