<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VehicleOperationalFileMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_operational_file_tables_are_installed(): void
    {
        foreach (['vehicle_compliance_records', 'vehicle_insurance_policies', 'vehicle_service_records', 'vehicle_incidents'] as $table) {
            self::assertTrue(Schema::hasTable($table), $table);
        }

        self::assertTrue(Schema::hasColumns('vehicle_compliance_records', ['record_uid', 'compliance_type', 'valid_from', 'valid_until', 'status', 'result', 'odometer', 'primary_document_id', 'revision']));
        self::assertTrue(Schema::hasColumns('vehicle_insurance_policies', ['record_uid', 'policy_type', 'insurer_name', 'policy_number', 'valid_from', 'valid_until', 'coverage_amount', 'deductible_amount', 'currency', 'revision']));
        self::assertTrue(Schema::hasColumns('vehicle_service_records', ['record_uid', 'service_type', 'status', 'opened_at', 'completed_at', 'next_service_on', 'odometer', 'next_service_odometer', 'revision']));
        self::assertTrue(Schema::hasColumns('vehicle_incidents', ['record_uid', 'incident_type', 'occurred_at', 'reported_at', 'resolved_at', 'status', 'severity', 'insurance_claim_reference', 'revision']));
    }
}
