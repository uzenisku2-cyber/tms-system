<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleComplianceRecord;
use App\Modules\Fleet\Models\VehicleDocument;
use App\Modules\Fleet\Models\VehicleRegistryEvent;
use App\Modules\Fleet\Models\VehicleResponsibility;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class VehicleComplianceEvidenceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_registration_and_correction_are_append_only_revisioned_and_audited(): void
    {
        [$user, $organization, $vehicle, $document] = $this->context();
        Sanctum::actingAs($user);
        $this->withHeaders(['X-Organization-ID' => (string) $organization->id]);
        $url = '/api/v1/vehicle-registry-administration/'.$vehicle->public_id.'/compliance-records';
        $data = $this->evidence($document);

        $created = $this->postJson($url, $data)->assertCreated()
            ->assertJsonPath('vehicle.revision', 2)
            ->assertJsonPath('compliance_record.revision', 1)
            ->json();
        $firstId = $created['compliance_record']['public_id'];
        $uid = $created['compliance_record']['record_uid'];
        self::assertSame('vehicle_compliance_evidence_registered', VehicleRegistryEvent::query()->sole()->event_type);

        $this->postJson($url, $data)->assertStatus(409);
        $corrected = $this->putJson($url.'/'.$firstId.'/revisions', array_merge($data, [
            'expected_revision' => 2,
            'expected_compliance_revision' => 1,
            'status' => 'failed',
            'result' => 'failed',
            'reason' => 'Inspection result corrected.',
        ]))->assertCreated()->assertJsonPath('vehicle.revision', 3)
            ->assertJsonPath('compliance_record.revision', 2)->json();
        self::assertSame($uid, $corrected['compliance_record']['record_uid']);
        self::assertNotSame($firstId, $corrected['compliance_record']['public_id']);
        self::assertSame(2, VehicleComplianceRecord::query()->where('record_uid', $uid)->count());
        self::assertSame('valid', VehicleComplianceRecord::query()->where('public_id', $firstId)->sole()->status);
        self::assertSame(2, VehicleRegistryEvent::query()->where('vehicle_id', $vehicle->id)->count());

        $this->putJson($url.'/'.$firstId.'/revisions', array_merge($data, [
            'expected_revision' => 3,
            'expected_compliance_revision' => 1,
        ]))->assertStatus(409);
        self::assertSame(2, VehicleComplianceRecord::query()->count());
        self::assertSame(3, (int) $vehicle->fresh()->current_revision);
    }

    public function test_unverified_and_foreign_documents_cannot_be_used(): void
    {
        [$user, $organization, $vehicle, $document] = $this->context();
        Sanctum::actingAs($user);
        $this->withHeaders(['X-Organization-ID' => (string) $organization->id]);
        $url = '/api/v1/vehicle-registry-administration/'.$vehicle->public_id.'/compliance-records';
        $document->update(['verification_status' => 'unverified']);
        $this->postJson($url, $this->evidence($document))->assertNotFound();
        $document->update(['verification_status' => 'verified', 'organization_context_id' => Organization::query()->create([
            'name' => 'Other organization',
            'type' => Organization::TYPE_MASTER,
            'status' => Organization::STATUS_ACTIVE,
        ])->id]);
        $this->postJson($url, $this->evidence($document))->assertNotFound();
        self::assertSame(0, VehicleComplianceRecord::query()->count());
        self::assertSame(1, (int) $vehicle->fresh()->current_revision);
    }

    /** @return array<string, mixed> */
    private function evidence(VehicleDocument $document): array
    {
        return [
            'expected_revision' => 1,
            'source_document_public_id' => $document->public_id,
            'compliance_type' => 'technical_inspection',
            'valid_from' => '2026-09-01',
            'valid_until' => '2027-09-01',
            'status' => 'valid',
            'result' => 'passed',
            'reason' => 'Inspection evidence registered.',
        ];
    }

    /** @return array{User, Organization, Vehicle, VehicleDocument} */
    private function context(): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Compliance test organization',
            'type' => Organization::TYPE_MASTER,
            'status' => Organization::STATUS_ACTIVE,
        ]);
        OrganizationMembership::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
        ]);
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        try {
            $registrar->setPermissionsTeamId((int) $organization->id);
            $registrar->forgetCachedPermissions();
            foreach (['vehicle.view', 'vehicle.manage'] as $name) {
                $user->givePermissionTo(Permission::findOrCreate($name, 'web'));
            }
        } finally {
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
            $registrar->setPermissionsTeamId($previous);
            $registrar->forgetCachedPermissions();
        }
        $vehicle = Vehicle::query()->create([
            'user_id' => $user->id,
            'registration_number' => 'S105-TEST',
            'manufacturer' => 'Test',
            'model' => 'Vehicle',
            'lifecycle_status' => 'active',
            'active' => true,
            'current_revision' => 1,
        ]);
        VehicleResponsibility::query()->create([
            'public_id' => (string) Str::uuid(),
            'vehicle_id' => $vehicle->id,
            'organization_context_id' => $organization->id,
            'responsibility_type' => 'operational_organization',
            'party_type' => 'organization',
            'party_organization_id' => $organization->id,
            'valid_from' => now()->subDay(),
            'source' => 'compliance_test',
            'status' => 'active',
            'recorded_by_user_id' => $user->id,
            'reason' => 'Compliance fixture.',
        ]);
        $document = VehicleDocument::query()->create([
            'public_id' => (string) Str::uuid(),
            'vehicle_id' => $vehicle->id,
            'organization_context_id' => $organization->id,
            'document_type' => 'technical_inspection',
            'title' => 'Inspection document',
            'storage_reference' => 'test/inspection',
            'verification_status' => 'verified',
            'access_classification' => 'operational',
            'uploaded_by_user_id' => $user->id,
            'revision' => 1,
        ]);

        return [$user, $organization, $vehicle, $document];
    }
}
