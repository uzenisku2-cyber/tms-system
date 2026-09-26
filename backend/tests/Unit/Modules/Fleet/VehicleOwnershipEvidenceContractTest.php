<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

final class VehicleOwnershipEvidenceContractTest extends TestCase
{
    public function test_ownership_evidence_administration_boundaries_are_explicit(): void
    {
        $root = dirname(__DIR__, 4);
        $service = file_get_contents($root.'/app/Modules/Fleet/Services/VehicleOwnershipEvidenceService.php');
        $storeRequest = file_get_contents($root.'/app/Modules/Fleet/Requests/StoreVehicleOwnershipRequest.php');
        $reviewRequest = file_get_contents($root.'/app/Modules/Fleet/Requests/ReviewVehicleOwnershipRequest.php');
        $routes = file_get_contents($root.'/app/Modules/Fleet/Routes/api.php');
        self::assertIsString($service);
        self::assertIsString($storeRequest);
        self::assertIsString($reviewRequest);
        self::assertIsString($routes);
        self::assertStringContainsString("can('vehicle.manage')", $service);
        self::assertStringContainsString('organization_context_id', $service);
        self::assertStringContainsString('lockForUpdate()', $service);
        self::assertStringContainsString('expected_revision', $storeRequest);
        self::assertStringContainsString('expected_ownership_revision', $reviewRequest);
        self::assertStringContainsString("Rule::in(['organization', 'user', 'external_party'])", $storeRequest);
        self::assertStringNotContainsString('financing_provider', $storeRequest);
        self::assertStringContainsString("where('verification_status', 'verified')", $service);
        self::assertStringContainsString("'verification_status' => 'unverified'", $service);
        self::assertStringContainsString("Rule::in(['verified', 'rejected'])", $reviewRequest);
        self::assertStringContainsString('VehicleRegistryEvent::query()->create', $service);
        self::assertStringContainsString('vehicle_ownership_evidence_registered', $service);
        self::assertStringContainsString('vehicle_ownership_evidence_reviewed', $service);
        self::assertStringContainsString("'source_document_public_id' => \$document->public_id", $service);
        self::assertStringContainsString('ownerships/{ownership}/verification', $routes);
        self::assertStringNotContainsString('->delete()', $service);
        self::assertStringNotContainsString('VehicleInsurancePolicy::query()', $service);
        self::assertStringNotContainsString('VehicleFinancingAgreement::query()', $service);
    }
}
