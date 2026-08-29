<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fuel;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FuelSurchargeDataContractTest extends TestCase
{
    #[Test]
    public function fuel_surcharge_is_independent_from_price_lists_and_cards(): void
    {
        $migration = $this->migrationSource();

        self::assertStringContainsString("Schema::create('fuel_surcharges'", $migration);
        self::assertStringContainsString("Schema::create('fuel_surcharge_recipient_rates'", $migration);
        self::assertStringContainsString('billing_rate_per_actual_km', $migration);
        self::assertStringContainsString('payout_rate_per_actual_km', $migration);
        self::assertStringContainsString('driver_organization_assignment_id', $migration);
        self::assertStringContainsString('carrier_relationship_id', $migration);
        self::assertStringNotContainsString("constrained('price_lists')", $migration);
        self::assertStringNotContainsString("constrained('fuel_cards')", $migration);
    }

    #[Test]
    public function recipient_identity_and_zero_rate_are_explicit(): void
    {
        $migration = $this->migrationSource();

        self::assertStringContainsString("recipient_type = 'own_driver'", $migration);
        self::assertStringContainsString("recipient_type = 'external_carrier'", $migration);
        self::assertStringContainsString('payout_rate_per_actual_km >= 0', $migration);
        self::assertStringContainsString('driver_organization_assignment_id IS NOT NULL', $migration);
        self::assertStringContainsString('carrier_relationship_id IS NOT NULL', $migration);
    }

    #[Test]
    public function database_guards_active_period_overlap(): void
    {
        $migration = $this->migrationSource();

        self::assertStringContainsString('guard_fuel_surcharge_overlap', $migration);
        self::assertStringContainsString('guard_fuel_surcharge_recipient_overlap', $migration);
        self::assertStringContainsString("existing.status = 'active'", $migration);
        self::assertStringContainsString('daterange(', $migration);
        self::assertStringContainsString("USING ERRCODE = '23514'", $migration);
        self::assertStringContainsString(
            'DROP FUNCTION IF EXISTS guard_fuel_surcharge_overlap() CASCADE',
            $migration,
        );
        self::assertStringContainsString(
            'DROP FUNCTION IF EXISTS guard_fuel_surcharge_recipient_overlap() CASCADE',
            $migration,
        );
    }

    private function migrationSource(): string
    {
        $path = database_path(
            'migrations/2026_08_28_233000_create_fuel_surcharge_management_foundation.php',
        );
        $source = file_get_contents($path);

        self::assertIsString($source);

        return $source;
    }
}
