<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

final class VehicleDocumentEvidenceContractTest extends TestCase
{
    public function test_document_evidence_administration_boundaries_are_explicit(): void
    {
        $root = dirname(__DIR__, 4);
        $service = file_get_contents($root.'/app/Modules/Fleet/Services/VehicleDocumentEvidenceService.php');
        $storeRequest = file_get_contents($root.'/app/Modules/Fleet/Requests/StoreVehicleDocumentEvidenceRequest.php');
        $reviewRequest = file_get_contents($root.'/app/Modules/Fleet/Requests/ReviewVehicleDocumentEvidenceRequest.php');
        $routes = file_get_contents($root.'/app/Modules/Fleet/Routes/api.php');

        self::assertStringContainsString("can('vehicle.manage')", $service);
        self::assertStringContainsString('organization_context_id', $service);
        self::assertStringContainsString('lockForUpdate()', $service);
        self::assertStringContainsString('expected_revision', $storeRequest);
        self::assertStringContainsString('expected_document_revision', $reviewRequest);
        self::assertStringContainsString("Rule::in(['verified', 'rejected'])", $reviewRequest);
        self::assertStringContainsString("'verification_status' => 'unverified'", $service);
        self::assertStringContainsString('VehicleRegistryEvent::query()->create', $service);
        self::assertStringContainsString('vehicle_document_evidence_registered', $service);
        self::assertStringContainsString('vehicle_document_evidence_reviewed', $service);
        self::assertStringContainsString('documents/{document}/verification', $routes);
        self::assertStringNotContainsString('->delete()', $service);
        self::assertStringNotContainsString('VehicleInsurancePolicy::query()', $service);
        self::assertStringNotContainsString('VehicleFinancingAgreement::query()', $service);
    }
}
