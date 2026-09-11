<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Modules\Pricing\Models\FinancialMutualCharge;
use PHPUnit\Framework\TestCase;

final class FinancialMutualChargeContractTest extends TestCase
{
    public function test_mutual_charge_contract_keeps_financial_boundaries_explicit(): void
    {
        self::assertSame(['organization', 'driver'], FinancialMutualCharge::PARTY_TYPES);
        self::assertSame(['receivable', 'payable'], FinancialMutualCharge::DIRECTIONS);
        self::assertSame(
            ['fuel', 'vehicle_cost', 'damage', 'advance', 'rental', 'penalty', 'bonus', 'other'],
            FinancialMutualCharge::CATEGORIES,
        );
        self::assertSame(
            ['draft', 'confirmed', 'disputed', 'reversed'],
            FinancialMutualCharge::STATUSES,
        );

        $model = file_get_contents(__DIR__.'/../../../../app/Modules/Pricing/Models/FinancialMutualCharge.php');
        $migration = file_get_contents(__DIR__.'/../../../../database/migrations/2026_09_14_000000_create_financial_mutual_charge_foundation.php');

        self::assertIsString($model);
        self::assertIsString($migration);
        self::assertStringContainsString("'amount_minor'", $model);
        self::assertStringContainsString("'source_snapshot' => 'array'", $model);
        self::assertStringContainsString("'visibility_status'", $model);
        self::assertStringContainsString("'offset_eligible'", $model);
        self::assertStringContainsString("\$table->unsignedBigInteger('amount_minor')", $migration);
        self::assertStringContainsString('amount_minor > 0', $migration);
        self::assertStringNotContainsString('BillingDocument::query()', $model);
        self::assertStringNotContainsString('BankTransaction', $model);
        self::assertStringNotContainsString('payment_marked', $model);
    }
}
