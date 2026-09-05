<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VehicleCostAllocationMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_cost_allocation_tables_are_installed(): void
    {
        foreach (['vehicle_cost_allocations', 'vehicle_cost_allocation_lines', 'vehicle_cost_allocation_events'] as $table) {
            self::assertTrue(Schema::hasTable($table), $table);
        }
        self::assertTrue(Schema::hasColumns('vehicle_cost_allocations', ['allocation_uid', 'vehicle_id', 'source_type', 'net_amount', 'vat_amount', 'gross_amount', 'currency', 'status', 'revision']));
        self::assertTrue(Schema::hasColumns('vehicle_cost_allocation_lines', ['line_uid', 'cost_component', 'responsible_party_type', 'responsible_organization_id', 'responsible_user_id', 'settlement_mode', 'vat_treatment', 'revision']));
        self::assertTrue(Schema::hasColumns('vehicle_cost_allocation_events', ['vehicle_cost_allocation_id', 'event_type', 'evidence', 'actor_user_id', 'revision', 'occurred_at']));
    }
}
