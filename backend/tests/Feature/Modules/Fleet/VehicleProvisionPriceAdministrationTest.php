<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleDocument;
use App\Modules\Fleet\Models\VehicleProvisionAgreement;
use App\Modules\Fleet\Models\VehicleProvisionPrice;
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

final class VehicleProvisionPriceAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_registration_correction_history_and_stale_revisions(): void
    {
        [$user, $organization, $vehicle, $document] = $this->context();
        Sanctum::actingAs($user);
        $this->withHeaders(['X-Organization-ID' => (string) $organization->id]);
        $agreement = $this->agreement($vehicle, $organization, $user);
        $url = '/api/v1/vehicle-registry-administration/'.$vehicle->public_id.'/provision-prices';
        $data = $this->price($agreement, $document);
        $created = $this->postJson($url, $data)->assertCreated()
            ->assertJsonPath('vehicle.revision', 2)->assertJsonPath('provision_price.revision', 1)->json();
        $first = $created['provision_price']['public_id'];
        $uid = $created['provision_price']['price_uid'];
        $this->postJson($url, $data)->assertStatus(409);
        $corrected = $this->putJson($url.'/'.$first.'/revisions', array_merge($data, [
            'expected_revision' => 2, 'expected_price_revision' => 1,
            'amount' => '1500.00', 'reason' => 'Correct provision price.',
        ]))->assertCreated()->assertJsonPath('vehicle.revision', 3)
            ->assertJsonPath('provision_price.revision', 2)->json();
        self::assertSame($uid, $corrected['provision_price']['price_uid']);
        self::assertNotSame($first, $corrected['provision_price']['public_id']);
        self::assertSame('1200.00', VehicleProvisionPrice::query()->where('public_id', $first)->sole()->amount);
        self::assertSame($document->public_id, VehicleRegistryEvent::query()->latest('id')->firstOrFail()->payload['source_document_public_id']);
        $this->getJson('/api/v1/vehicle-registry-administration/'.$vehicle->public_id)
            ->assertOk()->assertJsonCount(2, 'provision_prices');
        $this->putJson($url.'/'.$first.'/revisions', array_merge($data, [
            'expected_revision' => 3, 'expected_price_revision' => 1,
        ]))->assertStatus(409);
        self::assertSame(2, VehicleProvisionPrice::query()->count());
    }

    public function test_invalid_price_document_and_stale_agreement_do_not_mutate(): void
    {
        [$user, $organization, $vehicle, $document] = $this->context();
        Sanctum::actingAs($user);
        $this->withHeaders(['X-Organization-ID' => (string) $organization->id]);
        $agreement = $this->agreement($vehicle, $organization, $user);
        $url = '/api/v1/vehicle-registry-administration/'.$vehicle->public_id.'/provision-prices';
        $data = $this->price($agreement, $document);
        $document->update(['verification_status' => 'unverified']);
        $this->postJson($url, $data)->assertNotFound();
        $document->update(['verification_status' => 'verified']);
        foreach ([['amount' => '-1'], ['currency' => 'czk'], ['billing_period' => 'invalid'],
            ['vat_rate_basis_points' => 10001], ['valid_from' => '2026-08-01'],
            ['expected_provision_revision' => 9]] as $invalid) {
            $response = $this->postJson($url, array_merge($data, $invalid));
            $response->assertStatus(isset($invalid['expected_provision_revision']) ? 409 : 422);
        }
        self::assertSame(0, VehicleProvisionPrice::query()->count());
        self::assertSame(1, (int) $vehicle->fresh()->current_revision);
    }

    /** @return array<string, mixed> */
    private function price(VehicleProvisionAgreement $agreement, VehicleDocument $document): array
    {
        return [
            'expected_revision' => 1,
            'expected_provision_revision' => 1,
            'provision_public_id' => $agreement->public_id,
            'source_document_public_id' => $document->public_id,
            'valid_from' => '2026-09-01',
            'valid_until' => '2026-09-30',
            'amount' => '1200.00',
            'currency' => 'CZK',
            'billing_period' => 'monthly',
            'billing_mode' => 'manual_review',
            'vat_mode' => 'pending_review',
            'reason' => 'Register vehicle provision price.',
        ];
    }

    private function agreement(Vehicle $vehicle, Organization $organization, User $user): VehicleProvisionAgreement
    {
        return VehicleProvisionAgreement::query()->create([
            'vehicle_id' => $vehicle->id, 'organization_context_id' => $organization->id,
            'provider_type' => 'organization', 'provider_organization_id' => $organization->id,
            'recipient_type' => 'organization', 'recipient_organization_id' => $organization->id,
            'provision_mode' => 'rental', 'valid_from' => '2026-09-01', 'valid_until' => '2026-09-30',
            'status' => 'active', 'recorded_by_user_id' => $user->id, 'revision' => 1,
        ]);
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
