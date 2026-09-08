<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

final class CsobBankStatementCsvAdapterContractTest extends TestCase
{
    public function test_adapter_is_bank_specific_and_contains_no_matching_or_payment_side_effects(): void
    {
        $s = file_get_contents(__DIR__.'/../../../../app/Modules/Fleet/Services/CsobBankStatementCsvAdapter.php');
        self::assertIsString($s);
        foreach (['ID transakce', 'datum zaúčtování', 'value_date', 'original_source_reference', 'UTF-8 with BOM'] as $v) {
            self::assertStringContainsString($v, $s);
        }foreach (['VehicleCostAllocationBankMatchingExecution', 'BillingDocument', 'payment_id', '->record('] as $v) {
            self::assertStringNotContainsString($v, $s);
        }
    }
}
