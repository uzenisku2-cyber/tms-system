<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleDocument;
use App\Modules\Fleet\Models\VehicleFinancingAgreement;
use App\Modules\Fleet\Models\VehicleInstallment;
use App\Modules\Fleet\Models\VehicleInstallmentSchedule;
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

final class VehicleInstallmentAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_append_only_installments_history_and_conflicts(): void
    {
        [$user, $organization, $vehicle, $document] = $this->context();
        $financing = $this->financing($vehicle, $organization, $user);
        $schedule = VehicleInstallmentSchedule::query()->create([
            'vehicle_financing_agreement_id' => $financing->id,
            'starts_on' => '2026-10-01', 'ends_on' => '2027-09-30',
            'installment_count' => 12, 'planned_total_amount' => '9000.00',
            'currency' => 'CZK', 'frequency' => 'monthly', 'status' => 'draft',
            'recorded_by_user_id' => $user->id, 'revision' => 1,
        ]);
        Sanctum::actingAs($user);
        $this->withHeaders(['X-Organization-ID' => (string) $organization->id]);
        $url = '/api/v1/vehicle-registry-administration/'.$vehicle->public_id.'/installments';
        $data = [
            'expected_revision' => 1,
            'schedule_public_id' => $schedule->public_id,
            'expected_schedule_revision' => 1,
            'source_document_public_id' => $document->public_id,
            'sequence_number' => 1, 'due_on' => '2026-10-15',
            'principal_amount' => '700.00', 'finance_charge_amount' => '49.50',
            'other_amount' => '0.50', 'total_amount' => '750.00',
            'currency' => 'CZK', 'status' => 'planned', 'reason' => 'Register installment.',
        ];
        $this->postJson($url, array_merge($data, ['total_amount' => '751.00']))->assertUnprocessable();
        $this->postJson($url, array_merge($data, ['sequence_number' => 13]))->assertUnprocessable();
        $this->postJson($url, array_merge($data, ['due_on' => '2028-01-01']))->assertUnprocessable();
        $this->postJson($url, array_merge($data, ['currency' => 'EUR']))->assertUnprocessable();
        self::assertSame(0, VehicleInstallment::query()->count());
        $created = $this->postJson($url, $data)->assertCreated()
            ->assertJsonPath('vehicle.revision', 2)->assertJsonPath('installment.revision', 1)->json();
        $first = $created['installment']['public_id'];
        $uid = $created['installment']['installment_uid'];
        $this->postJson($url, array_merge($data, ['expected_revision' => 2]))->assertStatus(409);
        $revision = array_merge($data, ['expected_revision' => 2, 'expected_installment_revision' => 1,
            'principal_amount' => '710.00', 'total_amount' => '760.00', 'reason' => 'Correct principal amount.']);
        $corrected = $this->putJson($url.'/'.$first.'/revisions', $revision)->assertCreated()
            ->assertJsonPath('installment.revision', 2)->assertJsonPath('vehicle.revision', 3)->json();
        self::assertSame($uid, $corrected['installment']['installment_uid']);
        self::assertNotSame($first, $corrected['installment']['public_id']);
        self::assertSame('750.00', VehicleInstallment::query()->where('public_id', $first)->sole()->total_amount);
        self::assertSame(2, VehicleRegistryEvent::query()->count());
        $this->getJson('/api/v1/vehicle-registry-administration/'.$vehicle->public_id)
            ->assertOk()->assertJsonCount(2, 'installments');
        $this->putJson($url.'/'.$first.'/revisions', array_merge($revision, ['expected_revision' => 3]))->assertStatus(409);
        $document->update(['verification_status' => 'unverified']);
        $this->postJson($url, array_merge($data, ['expected_revision' => 3, 'sequence_number' => 2]))->assertNotFound();
        self::assertSame(2, VehicleInstallment::query()->count());
        self::assertSame(3, (int) $vehicle->fresh()->current_revision);
    }

    private function financing(Vehicle $vehicle, Organization $organization, User $user): VehicleFinancingAgreement
    {
        return VehicleFinancingAgreement::query()->create([
            'vehicle_id' => $vehicle->id,
            'organization_context_id' => $organization->id,
            'financing_type' => 'finance_lease',
            'financier_organization_id' => $organization->id,
            'debtor_type' => 'organization',
            'debtor_organization_id' => $organization->id,
            'effective_from' => '2026-09-01',
            'effective_until' => '2027-12-31',
            'currency' => 'CZK',
            'total_amount' => '10000.00',
            'status' => 'draft',
            'recorded_by_user_id' => $user->id,
            'revision' => 1,
        ]);
    }

    /** @return array{User, Organization, Vehicle, VehicleDocument} */
    private function context(): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Schedule test organization',
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
            'registration_number' => 'S115-TEST',
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
