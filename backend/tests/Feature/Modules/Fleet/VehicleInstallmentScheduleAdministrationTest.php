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

final class VehicleInstallmentScheduleAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_registration_correction_history_and_conflicts(): void
    {
        [$user, $organization, $vehicle, $document] = $this->context();
        $financing = $this->financing($vehicle, $organization, $user);
        Sanctum::actingAs($user);
        $this->withHeaders(['X-Organization-ID' => (string) $organization->id]);
        $url = '/api/v1/vehicle-registry-administration/'.$vehicle->public_id.'/installment-schedules';
        $data = $this->schedule($financing, $document);
        $created = $this->postJson($url, $data)->assertCreated()
            ->assertJsonPath('vehicle.revision', 2)->assertJsonPath('installment_schedule.revision', 1)->json();
        $first = $created['installment_schedule']['public_id'];
        $uid = $created['installment_schedule']['schedule_uid'];
        $this->postJson($url, $data)->assertStatus(409);
        $corrected = $this->putJson($url.'/'.$first.'/revisions', array_merge($data, [
            'expected_revision' => 2,
            'expected_schedule_revision' => 1,
            'planned_total_amount' => '9100.00',
            'reason' => 'Correct schedule amount.',
        ]))->assertCreated()->assertJsonPath('vehicle.revision', 3)
            ->assertJsonPath('installment_schedule.revision', 2)->json();
        self::assertSame($uid, $corrected['installment_schedule']['schedule_uid']);
        self::assertNotSame($first, $corrected['installment_schedule']['public_id']);
        self::assertSame('9000.00', VehicleInstallmentSchedule::query()->where('public_id', $first)->sole()->planned_total_amount);
        self::assertSame(2, VehicleInstallmentSchedule::query()->where('schedule_uid', $uid)->count());
        self::assertSame($document->public_id, VehicleRegistryEvent::query()->firstOrFail()->payload['source_document_public_id']);
        self::assertSame(2, VehicleRegistryEvent::query()->count());
        $this->getJson('/api/v1/vehicle-registry-administration/'.$vehicle->public_id)->assertOk()
            ->assertJsonCount(2, 'installment_schedules');
        $this->putJson($url.'/'.$first.'/revisions', array_merge($data, [
            'expected_revision' => 3, 'expected_schedule_revision' => 1,
        ]))->assertStatus(409);
        self::assertSame(3, (int) $vehicle->fresh()->current_revision);
    }

    public function test_invalid_parent_document_and_currency_do_not_mutate(): void
    {
        [$user, $organization, $vehicle, $document] = $this->context();
        $financing = $this->financing($vehicle, $organization, $user);
        Sanctum::actingAs($user);
        $this->withHeaders(['X-Organization-ID' => (string) $organization->id]);
        $url = '/api/v1/vehicle-registry-administration/'.$vehicle->public_id.'/installment-schedules';
        $data = $this->schedule($financing, $document);
        $document->update(['verification_status' => 'unverified']);
        $this->postJson($url, $data)->assertNotFound();
        $document->update(['verification_status' => 'verified']);
        $this->postJson($url, array_merge($data, ['currency' => 'EUR']))->assertUnprocessable();
        $this->postJson($url, array_merge($data, ['installment_count' => 0]))->assertUnprocessable();
        $this->postJson($url, array_merge($data, ['starts_on' => '2026-08-01']))->assertUnprocessable();
        $this->postJson($url, array_merge($data, ['financing_public_id' => (string) Str::uuid()]))->assertNotFound();
        $foreign = Organization::query()->create(['name' => 'Foreign organization', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $other = $this->financing($vehicle, $foreign, $user);
        $this->postJson($url, array_merge($data, ['financing_public_id' => $other->public_id]))->assertNotFound();
        self::assertSame(0, VehicleInstallmentSchedule::query()->count());
        self::assertSame(1, (int) $vehicle->fresh()->current_revision);
    }

    public function test_financing_revision_change_requires_a_new_schedule(): void
    {
        [$user, $organization, $vehicle, $document] = $this->context();
        $financing = $this->financing($vehicle, $organization, $user);
        Sanctum::actingAs($user);
        $this->withHeaders(['X-Organization-ID' => (string) $organization->id]);
        $url = '/api/v1/vehicle-registry-administration/'.$vehicle->public_id.'/installment-schedules';
        $data = $this->schedule($financing, $document);
        $created = $this->postJson($url, $data)->assertCreated()->json();
        $newFinancing = $financing->replicate();
        $newFinancing->public_id = (string) Str::uuid();
        $newFinancing->revision = 2;
        $newFinancing->save();
        $revision = array_merge($data, ['expected_revision' => 2, 'expected_schedule_revision' => 1]);
        $this->putJson($url.'/'.$created['installment_schedule']['public_id'].'/revisions', $revision)->assertStatus(409);
        $this->postJson($url, array_merge($data, ['expected_revision' => 2]))->assertStatus(409);
        $this->postJson($url, array_merge($data, [
            'expected_revision' => 2,
            'financing_public_id' => $newFinancing->public_id,
            'expected_financing_revision' => 2,
        ]))->assertCreated();
        self::assertSame(2, VehicleInstallmentSchedule::query()->count());
    }

    public function test_correction_rejects_changed_financing_and_recorded_installments(): void
    {
        [$user, $organization, $vehicle, $document] = $this->context();
        $financing = $this->financing($vehicle, $organization, $user);
        Sanctum::actingAs($user);
        $this->withHeaders(['X-Organization-ID' => (string) $organization->id]);
        $url = '/api/v1/vehicle-registry-administration/'.$vehicle->public_id.'/installment-schedules';
        $data = $this->schedule($financing, $document);
        $created = $this->postJson($url, $data)->assertCreated()->json();
        $id = $created['installment_schedule']['public_id'];
        $schedule = VehicleInstallmentSchedule::query()->where('public_id', $id)->sole();
        VehicleInstallment::query()->create([
            'vehicle_installment_schedule_id' => $schedule->id,
            'sequence_number' => 1, 'due_on' => '2026-10-01',
            'principal_amount' => '100.00', 'finance_charge_amount' => '0.00',
            'other_amount' => '0.00', 'total_amount' => '100.00',
            'currency' => 'CZK', 'status' => 'planned',
            'recorded_by_user_id' => $user->id, 'revision' => 1,
        ]);
        $revision = array_merge($data, ['expected_revision' => 2, 'expected_schedule_revision' => 1]);
        $this->putJson($url.'/'.$id.'/revisions', $revision)->assertStatus(409);
        self::assertSame(1, VehicleInstallmentSchedule::query()->count());
        self::assertSame(2, (int) $vehicle->fresh()->current_revision);
    }

    /** @return array<string, mixed> */
    private function schedule(VehicleFinancingAgreement $financing, VehicleDocument $document): array
    {
        return [
            'expected_revision' => 1,
            'financing_public_id' => $financing->public_id,
            'expected_financing_revision' => 1,
            'source_document_public_id' => $document->public_id,
            'starts_on' => '2026-10-01',
            'ends_on' => '2027-09-30',
            'installment_count' => 12,
            'planned_total_amount' => '9000.00',
            'currency' => 'CZK',
            'frequency' => 'monthly',
            'status' => 'draft',
            'reason' => 'Register planned schedule.',
        ];
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
