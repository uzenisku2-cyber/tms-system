<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleDocument;
use App\Modules\Fleet\Models\VehicleFinancingAgreement;
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

final class VehicleFinancingEvidenceTest extends TestCase
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
        $url = '/api/v1/vehicle-registry-administration/'.$vehicle->public_id.'/financing-agreements';
        $data = $this->evidence($document, $organization);
        $created = $this->postJson($url, $data)->assertCreated()
            ->assertJsonPath('vehicle.revision', 2)
            ->assertJsonPath('financing_agreement.revision', 1)->json();
        $firstId = $created['financing_agreement']['public_id'];
        $uid = $created['financing_agreement']['financing_uid'];
        self::assertSame($document->public_id, VehicleRegistryEvent::query()->sole()->payload['source_document_public_id']);
        $this->postJson($url, $data)->assertStatus(409);
        $corrected = $this->putJson($url.'/'.$firstId.'/revisions', array_merge($data, [
            'expected_revision' => 2,
            'expected_financing_revision' => 1,
            'total_amount' => '12000.00',
            'reason' => 'Financing contract correction.',
        ]))->assertCreated()->assertJsonPath('vehicle.revision', 3)
            ->assertJsonPath('financing_agreement.revision', 2)->json();
        self::assertSame($uid, $corrected['financing_agreement']['financing_uid']);
        self::assertNotSame($firstId, $corrected['financing_agreement']['public_id']);
        self::assertSame(2, VehicleFinancingAgreement::query()->where('financing_uid', $uid)->count());
        self::assertSame('10000.00', VehicleFinancingAgreement::query()->where('public_id', $firstId)->sole()->total_amount);
        self::assertSame(2, VehicleRegistryEvent::query()->count());
        $this->putJson($url.'/'.$firstId.'/revisions', array_merge($data, [
            'expected_revision' => 3,
            'expected_financing_revision' => 1,
        ]))->assertStatus(409);
        self::assertSame(2, VehicleFinancingAgreement::query()->count());
        self::assertSame(3, (int) $vehicle->fresh()->current_revision);
    }

    public function test_invalid_parties_and_unverified_document_are_rejected_without_mutation(): void
    {
        [$user, $organization, $vehicle, $document] = $this->context();
        Sanctum::actingAs($user);
        $this->withHeaders(['X-Organization-ID' => (string) $organization->id]);
        $url = '/api/v1/vehicle-registry-administration/'.$vehicle->public_id.'/financing-agreements';
        $data = $this->evidence($document, $organization);
        $document->update(['verification_status' => 'unverified']);
        $this->postJson($url, $data)->assertNotFound();
        $document->update(['verification_status' => 'verified']);
        $this->postJson($url, array_merge($data, ['external_financier_name' => 'Second financier']))->assertUnprocessable();
        $this->postJson($url, array_merge($data, ['total_amount' => '-1']))->assertUnprocessable();
        $this->postJson($url, array_merge($data, ['currency' => 'czk']))->assertUnprocessable();
        $this->postJson($url, array_merge($data, ['debtor_type' => 'driver', 'debtor_organization_id' => null, 'debtor_user_id' => User::factory()->create()->id]))->assertUnprocessable();
        self::assertSame(0, VehicleFinancingAgreement::query()->count());
        self::assertSame(1, (int) $vehicle->fresh()->current_revision);
    }

    /** @return array<string, mixed> */
    private function evidence(VehicleDocument $document, Organization $organization): array
    {
        return [
            'expected_revision' => 1,
            'source_document_public_id' => $document->public_id,
            'financing_type' => 'finance_lease',
            'financier_organization_id' => $organization->id,
            'debtor_type' => 'organization',
            'debtor_organization_id' => $organization->id,
            'agreement_number' => 'LEASE-113',
            'effective_from' => '2026-09-01',
            'currency' => 'CZK',
            'total_amount' => '10000.00',
            'status' => 'draft',
            'reason' => 'Financing evidence registered.',
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
            'document_type' => 'financing_agreement',
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
