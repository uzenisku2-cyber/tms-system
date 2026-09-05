<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

class VehicleOperationalFileContractTest extends TestCase
{
    public function test_operational_file_is_revisioned_append_only_and_financially_separate(): void
    {
        $migration = file_get_contents(__DIR__.'/../../../../database/migrations/2026_09_06_020000_create_vehicle_operational_file_compliance_foundation.php');
        $vehicle = file_get_contents(__DIR__.'/../../../../app/Modules/Fleet/Models/Vehicle.php');
        self::assertIsString($migration);
        self::assertIsString($vehicle);

        foreach (['vehicle_compliance_records', 'vehicle_insurance_policies', 'vehicle_service_records', 'vehicle_incidents', 'record_uid', 'primary_document_id', 'vehicle_compliance_record_revision_unique', 'vehicle_incident_revision_unique'] as $marker) {
            self::assertStringContainsString($marker, $migration);
        }
        foreach (['complianceRecords', 'insurancePolicies', 'serviceRecords', 'incidents'] as $marker) {
            self::assertStringContainsString($marker, $vehicle);
        }
        foreach (['VehicleComplianceRecord.php', 'VehicleInsurancePolicy.php', 'VehicleServiceRecord.php', 'VehicleIncident.php'] as $model) {
            $source = file_get_contents(__DIR__.'/../../../../app/Modules/Fleet/Models/'.$model);
            self::assertIsString($source);
            self::assertStringContainsString('append-only', $source);
            self::assertStringContainsString('record_uid', $source);
        }
        self::assertStringNotContainsString('financial_calculation_id', $migration);
        self::assertStringNotContainsString('billing_document_id', $migration);
        self::assertStringNotContainsString('bank_transaction_id', $migration);
    }
}
