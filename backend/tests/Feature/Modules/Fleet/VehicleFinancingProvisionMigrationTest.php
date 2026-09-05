<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VehicleFinancingProvisionMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_financing_and_provision_tables_are_installed(): void
    {
        foreach (['vehicle_provision_agreements', 'vehicle_provision_prices', 'vehicle_financing_agreements', 'vehicle_installment_schedules', 'vehicle_installments'] as $table) {
            self::assertTrue(Schema::hasTable($table), $table);
        }
        self::assertTrue(Schema::hasColumns('vehicle_provision_agreements', ['agreement_uid', 'provider_type', 'recipient_type', 'provision_mode', 'valid_from', 'valid_until', 'revision']));
        self::assertTrue(Schema::hasColumns('vehicle_provision_prices', ['price_uid', 'amount', 'currency', 'billing_period', 'billing_mode', 'vat_mode', 'revision']));
        self::assertTrue(Schema::hasColumns('vehicle_financing_agreements', ['financing_uid', 'financing_type', 'debtor_type', 'total_amount', 'initial_payment_amount', 'residual_value_amount', 'revision']));
        self::assertTrue(Schema::hasColumns('vehicle_installment_schedules', ['schedule_uid', 'installment_count', 'planned_total_amount', 'frequency', 'revision']));
        self::assertTrue(Schema::hasColumns('vehicle_installments', ['installment_uid', 'sequence_number', 'due_on', 'principal_amount', 'finance_charge_amount', 'total_amount', 'revision']));
    }
}
