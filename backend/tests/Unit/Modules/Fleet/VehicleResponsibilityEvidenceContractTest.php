<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

final class VehicleResponsibilityEvidenceContractTest extends TestCase
{
    public function test_responsibility_evidence_administration_boundaries_are_explicit(): void
    {
        $root = dirname(__DIR__, 4);
        $service = file_get_contents($root.'/app/Modules/Fleet/Services/VehicleResponsibilityEvidenceService.php');
        $storeRequest = file_get_contents($root.'/app/Modules/Fleet/Requests/StoreVehicleResponsibilityRequest.php');
        $reviewRequest = file_get_contents($root.'/app/Modules/Fleet/Requests/ReviewVehicleResponsibilityRequest.php');
        $routes = file_get_contents($root.'/app/Modules/Fleet/Routes/api.php');

        self::assertIsString($service);
        self::assertIsString($storeRequest);
        self::assertIsString($reviewRequest);
        self::assertIsString($routes);
        self::assertStringContainsString("can('vehicle.manage')", $service);
        self::assertStringContainsString('organization_context_id', $service);
        self::assertStringContainsString('lockForUpdate()', $service);
        self::assertStringContainsString('expected_revision', $storeRequest);
        self::assertStringContainsString('expected_responsibility_revision', $reviewRequest);
        self::assertStringContainsString("Rule::in(['registered_operator', 'operational_organization', 'custodian', 'authorized_user', 'default_driver'])", $storeRequest);
        self::assertStringContainsString("Rule::in(['organization', 'user', 'external_party'])", $storeRequest);
        self::assertStringNotContainsString('financing_provider', $storeRequest);
        self::assertStringContainsString("where('verification_status', 'verified')", $service);
        self::assertStringContainsString("'status' => 'active'", $service);
        self::assertStringContainsString("Rule::in(['active', 'ended', 'cancelled'])", $reviewRequest);
        self::assertStringContainsString('VehicleRegistryEvent::query()->create', $service);
        self::assertStringContainsString('vehicle_responsibility_evidence_registered', $service);
        self::assertStringContainsString('vehicle_responsibility_evidence_reviewed', $service);
        self::assertStringContainsString("'source_document_public_id' => \$document->public_id", $service);
        self::assertStringContainsString("'source' => 'document:'.\$document->public_id", $service);
        self::assertStringContainsString('responsibilities/{responsibility}/status', $routes);
        self::assertStringNotContainsString('->delete()', $service);
        self::assertStringNotContainsString('VehicleInsurancePolicy::query()', $service);
        self::assertStringNotContainsString('VehicleFinancingAgreement::query()', $service);
    }
}
