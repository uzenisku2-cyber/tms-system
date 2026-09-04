<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fuel;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class FuelTransactionSettlementApplicationMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_settlement_application_tables_are_installed(): void
    {
        self::assertTrue(Schema::hasTable('fuel_transaction_settlement_applications'));
        self::assertTrue(Schema::hasTable('fuel_transaction_settlement_application_events'));
        foreach (['fuel_transaction_id', 'fuel_transaction_settlement_eligibility_id', 'eligibility_revision', 'reconciliation_revision', 'applied_amount', 'currency', 'status', 'revision', 'applied_by_user_id', 'applied_at', 'financial_calculation_id'] as $column) {
            self::assertTrue(Schema::hasColumn('fuel_transaction_settlement_applications', $column), $column);
        }
    }
}
