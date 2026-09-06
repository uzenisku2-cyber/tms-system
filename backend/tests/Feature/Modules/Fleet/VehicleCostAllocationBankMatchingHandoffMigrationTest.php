<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class VehicleCostAllocationBankMatchingHandoffMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bank_matching_handoff_tables_are_installed(): void
    {
        self::assertTrue(Schema::hasTable('vehicle_cost_allocation_bank_matching_handoffs'));
        self::assertTrue(Schema::hasTable('vehicle_cost_allocation_bank_matching_handoff_events'));
        foreach (['financial_handoff_instruction_id', 'billing_document_id', 'organization_context_id', 'idempotency_key', 'bank_transaction_reference', 'evidence_amount', 'status', 'revision'] as $column) {
            self::assertTrue(Schema::hasColumn('vehicle_cost_allocation_bank_matching_handoffs', $column));
        }
    }
}
