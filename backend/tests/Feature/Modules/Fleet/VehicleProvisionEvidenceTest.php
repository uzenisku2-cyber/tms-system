<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleDocument;
use App\Modules\Fleet\Models\VehicleProvisionAgreement;
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

final class VehicleProvisionEvidenceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_registration_correction_history_and_optimistic_conflicts(): void
    {
        [$user, $organization, $vehicle, $document] = $this->context();
        Sanctum::actingAs($user);
        $this->withHeaders(['X-Organization-ID' => (string) $organization->id]);
        $url = '/api/v1/vehicle-registry-administration/'.$vehicle->public_id.'/provision-agreements';
        $data = $this->evidence($document, $organization);
        $created = $this->postJson($url, $data)->assertCreated()
            ->assertJsonPath('vehicle.revision', 2)->assertJsonPath('provision_agreement.revision', 1)->json();
        $first = $created['provision_agreement']['public_id'];
        $uid = $created['provision_agreement']['agreement_uid'];
        self::assertSame($document->public_id, VehicleRegistryEvent::query()->sole()->payload['source_document_public_id']);
        $this->postJson($url, $data)->assertStatus(409);
        $corrected = $this->putJson($url.'/'.$first.'/revisions', array_merge($data, [
            'expected_revision' => 2, 'expected_provision_revision' => 1,
            'agreement_number' => 'PROVISION-REVISED', 'reason' => 'Correct agreement number.',
        ]))->assertCreated()->assertJsonPath('vehicle.revision', 3)
            ->assertJsonPath('provision_agreement.revision', 2)->json();
        self::assertSame($uid, $corrected['provision_agreement']['agreement_uid']);
        self::assertNotSame($first, $corrected['provision_agreement']['public_id']);
        self::assertSame('PROVISION-119', VehicleProvisionAgreement::query()->where('public_id', $first)->sole()->agreement_number);
        self::assertSame(2, VehicleRegistryEvent::query()->count());
        $this->getJson('/api/v1/vehicle-registry-administration/'.$vehicle->public_id)
            ->assertOk()->assertJsonCount(2, 'provision_agreements');
        $this->putJson($url.'/'.$first.'/revisions', array_merge($data, [
            'expected_revision' => 3, 'expected_provision_revision' => 1,
        ]))->assertStatus(409);
    }

    public function test_invalid_parties_and_unverified_document_do_not_mutate(): void
    {
        [$user, $organization, $vehicle, $document] = $this->context();
        Sanctum::actingAs($user);
        $this->withHeaders(['X-Organization-ID' => (string) $organization->id]);
        $url = '/api/v1/vehicle-registry-administration/'.$vehicle->public_id.'/provision-agreements';
        $data = $this->evidence($document, $organization);
        $document->update(['verification_status' => 'unverified']);
        $this->postJson($url, $data)->assertNotFound();
        $document->update(['verification_status' => 'verified']);
        $this->postJson($url, array_merge($data, ['provider_user_id' => $user->id]))->assertUnprocessable();
        $this->postJson($url, array_merge($data, ['recipient_type' => 'driver', 'recipient_organization_id' => null, 'recipient_user_id' => User::factory()->create()->id]))->assertUnprocessable();
        $this->postJson($url, array_merge($data, ['provision_mode' => 'invalid']))->assertUnprocessable();
        $this->postJson($url, array_merge($data, ['valid_until' => '2026-08-01']))->assertUnprocessable();
        self::assertSame(0, VehicleProvisionAgreement::query()->count());
        self::assertSame(1, (int) $vehicle->fresh()->current_revision);
    }

    /** @return array<string, mixed> */
    private function evidence(VehicleDocument $document, Organization $organization): array
    {
        return [
            'expected_revision' => 1,
            'source_document_public_id' => $document->public_id,
            'provider_type' => 'organization',
            'provider_organization_id' => $organization->id,
            'recipient_type' => 'organization',
            'recipient_organization_id' => $organization->id,
            'provision_mode' => 'own_vehicle',
            'agreement_number' => 'PROVISION-119',
            'valid_from' => '2026-09-01',
            'status' => 'draft',
            'reason' => 'Register vehicle provision agreement.',
        ];
    }

    /** @return array{User, Organization, Vehicle, VehicleDocument} */
    private function context(): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Financing test organization',
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
            'registration_number' => 'S113-TEST',
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
            'source' => 'financing_test',
            'status' => 'active',
            'recorded_by_user_id' => $user->id,
            'reason' => 'Financing fixture.',
        ]);
        $document = VehicleDocument::query()->create([
            'public_id' => (string) Str::uuid(),
            'vehicle_id' => $vehicle->id,
            'organization_context_id' => $organization->id,
            'document_type' => 'provision_agreement',
            'title' => 'Financing contract',
            'storage_reference' => 'test/financing',
            'verification_status' => 'verified',
            'access_classification' => 'operational',
            'uploaded_by_user_id' => $user->id,
            'revision' => 1,
        ]);

        return [$user, $organization, $vehicle, $document];
    }
}
