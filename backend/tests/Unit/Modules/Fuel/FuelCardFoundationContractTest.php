<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fuel;

use Tests\TestCase;

final class FuelCardFoundationContractTest extends TestCase
{
    public function test_storage_contract(): void
    {
        $m = file_get_contents(database_path('migrations/2026_08_26_180000_create_fuel_card_management_foundation.php'));
        self::assertIsString($m);
        foreach (['fuel_cards', 'fuel_card_assignments', 'fuel_card_settlement_policies', 'fuel_card_events', 'ORLEN', 'MOL', 'discount_beneficiary', 'expires_at', 'before_payload', 'after_payload'] as $needle) {
            self::assertStringContainsString($needle, $m);
        } self::assertStringContainsString("settlement_target='driver' AND vat_mode='not_applicable'", $m);
    }
}
