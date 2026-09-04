<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VehicleRegistryFoundationMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_registry_foundation_is_installed(): void
    {
        self::assertTrue(Schema::hasColumns('vehicles', ['public_id', 'first_registered_on', 'odometer_unit', 'lifecycle_status', 'current_revision', 'archived_at']));
        foreach (['vehicle_ownerships', 'vehicle_responsibilities', 'vehicle_documents', 'vehicle_registry_events'] as $table) {
            self::assertTrue(Schema::hasTable($table), $table);
        }
        self::assertTrue(Schema::hasColumns('vehicle_ownerships', ['vehicle_id', 'owner_type', 'ownership_share_basis_points', 'valid_from', 'valid_until', 'verification_status', 'revision']));
        self::assertTrue(Schema::hasColumns('vehicle_responsibilities', ['responsibility_type', 'party_type', 'valid_from', 'valid_until', 'source', 'status', 'revision']));
        self::assertTrue(Schema::hasColumns('vehicle_documents', ['document_type', 'storage_reference', 'valid_until', 'verification_status', 'access_classification']));
        self::assertTrue(Schema::hasColumns('vehicle_registry_events', ['event_type', 'vehicle_revision', 'reason', 'payload', 'occurred_at']));
    }
}
