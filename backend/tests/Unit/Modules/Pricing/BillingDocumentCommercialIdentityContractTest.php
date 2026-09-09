<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentity;
use PHPUnit\Framework\TestCase;

final class BillingDocumentCommercialIdentityContractTest extends TestCase
{
    public function test_supplier_fuel_invoice_and_payment_direction_contracts_are_explicit(): void
    {
        self::assertSame('supplier_fuel_invoice', BillingDocument::TYPE_SUPPLIER_FUEL_INVOICE);
        self::assertSame('receivable', BillingDocumentCommercialIdentity::DIRECTION_RECEIVABLE);
        self::assertSame('payable', BillingDocumentCommercialIdentity::DIRECTION_PAYABLE);
    }

    public function test_foundation_does_not_execute_bank_matching_or_payment_marking(): void
    {
        $source = file_get_contents(__DIR__.'/../../../../app/Modules/Pricing/Models/BillingDocumentCommercialIdentity.php');
        self::assertIsString($source);
        self::assertStringNotContainsString('BankTransaction', $source);
        self::assertStringNotContainsString('paid_at', $source);
    }
}
