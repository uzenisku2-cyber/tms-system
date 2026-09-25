<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

final class VehicleRecordCompletionContractTest extends TestCase
{
    public function test_progressive_completion_preserves_vehicle_and_document_boundaries(): void
    {
        $root = dirname(__DIR__, 4);
        $service = file_get_contents($root.'/app/Modules/Fleet/Services/ProgressiveVehicleRecordService.php');
        $request = file_get_contents($root.'/app/Modules/Fleet/Requests/UpdateProgressiveVehicleRecordRequest.php');
        self::assertIsString($service);
        self::assertIsString($request);
        self::assertStringContainsString("can('vehicle.manage')", $service);
        self::assertStringContainsString('lockForUpdate()', $service);
        self::assertStringContainsString('expected_revision', $request);
        self::assertStringContainsString("'fields' => ['required', 'array', 'min:1']", $request);
        self::assertStringContainsString("array_key_exists('registration_number', \$normalizedFields)", $service);
        self::assertStringContainsString("array_key_exists('vin', \$normalizedFields)", $service);
        self::assertStringContainsString("'mileage' => \$value === null ? 0 : (int) \$value", $service);
        self::assertStringContainsString("where('organization_context_id', \$organizationId)", $service);
        self::assertStringContainsString("'progressive_vehicle_record_updated'", $service);
        self::assertStringContainsString("'source_document_public_id' => \$document?->public_id", $service);
        self::assertStringContainsString("verification_status === 'verified'", $service);
        self::assertStringNotContainsString('VehicleDocument::query()->create', $service);
        self::assertStringNotContainsString('->delete()', $service);
        self::assertStringNotContainsString('VehicleInsurancePolicy::query()', $service);
        self::assertStringNotContainsString('VehicleFinancingAgreement::query()', $service);
    }
}
