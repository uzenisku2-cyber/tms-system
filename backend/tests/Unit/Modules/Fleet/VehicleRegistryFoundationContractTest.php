<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

class VehicleRegistryFoundationContractTest extends TestCase
{
    public function test_registry_separates_identity_ownership_responsibility_documents_and_audit(): void
    {
        $migration = file_get_contents(__DIR__.'/../../../../database/migrations/2026_09_05_220000_create_vehicle_registry_ownership_operational_file_foundation.php');
        $vehicle = file_get_contents(__DIR__.'/../../../../app/Modules/Fleet/Models/Vehicle.php');
        $event = file_get_contents(__DIR__.'/../../../../app/Modules/Fleet/Models/VehicleRegistryEvent.php');
        self::assertIsString($migration);
        self::assertIsString($vehicle);
        self::assertIsString($event);
        foreach (['vehicle_ownerships', 'vehicle_responsibilities', 'vehicle_documents', 'vehicle_registry_events', 'organization_context_id', 'ownership_share_basis_points', 'access_classification', 'vehicle_registry_event_revision_unique'] as $marker) {
            self::assertStringContainsString($marker, $migration);
        }
        foreach (['ownerships', 'responsibilities', 'documents', 'registryEvents', 'public_id', 'lifecycle_status', 'archived_at'] as $marker) {
            self::assertStringContainsString($marker, $vehicle);
        }
        self::assertStringContainsString('append-only', $event);
        self::assertStringNotContainsString('financial_calculation_id', $migration);
        self::assertStringNotContainsString('bank_transaction_id', $migration);
    }
}
