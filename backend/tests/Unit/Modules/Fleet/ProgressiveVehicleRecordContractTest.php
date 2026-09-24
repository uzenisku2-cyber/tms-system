<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

final class ProgressiveVehicleRecordContractTest extends TestCase
{
    public function test_progressive_record_preserves_vehicle_boundaries(): void
    {
        $root = dirname(__DIR__, 4);
        $service = file_get_contents($root.'/app/Modules/Fleet/Services/ProgressiveVehicleRecordService.php');
        $migration = file_get_contents($root.'/database/migrations/2026_09_24_120000_create_progressive_vehicle_record_foundation.php');
        $readService = file_get_contents($root.'/app/Modules/Fleet/Services/VehicleRegistryAdministrationReadService.php');
        self::assertIsString($service);
        self::assertIsString($migration);
        self::assertIsString($readService);
        self::assertStringContainsString("can('vehicle.manage')", $service);
        self::assertStringContainsString('organization_context_id', $service);
        self::assertStringContainsString('required_without:vin', file_get_contents($root.'/app/Modules/Fleet/Requests/StoreProgressiveVehicleRecordRequest.php'));
        self::assertStringContainsString('lockForUpdate()', $service);
        self::assertStringContainsString('expected_revision', $service);
        self::assertStringContainsString('VehicleRegistryEvent::query()->create', $service);
        self::assertStringContainsString("'year', 'fuel_type', 'mileage'", $service);
        self::assertStringContainsString("array_key_exists('mileage', \$data)", $service);
        self::assertStringContainsString("'mileage' => \$mileageWasProvided", $service);
        self::assertStringContainsString("where('field_key', 'mileage')", $readService);
        self::assertStringContainsString("whereIn('status', ['missing', 'pending_document'])", $readService);
        self::assertStringContainsString("'mileage' => ".'$mileageIsUnknown ? null : $vehicle->mileage', $readService);
        self::assertStringNotContainsString('->delete()', $service);
        self::assertStringNotContainsString("'user_id' => \$actor->id", $service);
        self::assertStringContainsString("'missing','pending_document','unverified','verified','not_applicable'", str_replace(' ', '', $migration));
    }
}
