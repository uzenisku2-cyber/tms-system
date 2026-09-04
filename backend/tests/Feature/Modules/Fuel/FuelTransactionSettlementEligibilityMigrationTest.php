<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fuel;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class FuelTransactionSettlementEligibilityMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_settlement_eligibility_tables_are_installed(): void
    {
        self::assertTrue(Schema::hasTable('fuel_transaction_settlement_eligibilities'));
        self::assertTrue(Schema::hasTable('fuel_transaction_settlement_eligibility_evaluations'));
        foreach (['fuel_transaction_id', 'status', 'result_code', 'reconciliation_revision', 'revision'] as $column) {
            self::assertTrue(Schema::hasColumn('fuel_transaction_settlement_eligibilities', $column));
        }
    }
}
