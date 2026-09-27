<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
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

final class VehicleLifecycleTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_transition_increments_revision_and_audits_while_rejecting_stale_and_invalid_requests(): void
    {
        [$user, $organization, $vehicle] = $this->context();
        Sanctum::actingAs($user);
        $url = '/api/v1/vehicle-registry-administration/'.$vehicle->public_id.'/lifecycle';
        $headers = ['X-Organization-ID' => (string) $organization->id];

        $this->withHeaders($headers)->putJson($url, [
            'expected_revision' => 1,
            'target_status' => 'temporarily_inactive',
            'reason' => 'Vehicle temporarily out of service.',
        ])->assertOk()->assertJsonPath('vehicle.lifecycle_status', 'temporarily_inactive')
            ->assertJsonPath('vehicle.revision', 2)->assertJsonPath('vehicle.active', false);

        $vehicle->refresh();
        self::assertSame(2, $vehicle->current_revision);
        self::assertNull($vehicle->archived_at);
        $event = VehicleRegistryEvent::query()->where('vehicle_id', $vehicle->id)->sole();
        self::assertSame('vehicle_lifecycle_transitioned', $event->event_type);
        self::assertSame(2, $event->vehicle_revision);
        self::assertSame(['from_status' => 'active', 'to_status' => 'temporarily_inactive'], $event->payload);

        $this->withHeaders($headers)->putJson($url, [
            'expected_revision' => 1,
            'target_status' => 'active',
            'reason' => 'Stale revision.',
        ])->assertStatus(409);
        $this->withHeaders($headers)->putJson($url, [
            'expected_revision' => 2,
            'target_status' => 'archived',
            'reason' => 'Illegal shortcut.',
        ])->assertUnprocessable();
        self::assertSame(2, (int) $vehicle->fresh()->current_revision);
        self::assertSame(1, VehicleRegistryEvent::query()->where('vehicle_id', $vehicle->id)->count());
    }

    public function test_legacy_api_cannot_change_active_or_delete_vehicle(): void
    {
        [$user, , $vehicle] = $this->context();
        Sanctum::actingAs($user);
        $this->putJson('/api/v1/vehicles/'.$vehicle->id, ['active' => false])->assertUnprocessable();
        $this->deleteJson('/api/v1/vehicles/'.$vehicle->id)->assertStatus(405);
        self::assertTrue((bool) $vehicle->fresh()->active);
    }

    /** @return array{User, Organization, Vehicle} */
    private function context(): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Lifecycle test organization',
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
            'registration_number' => 'S103-TEST',
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
            'source' => 'lifecycle_test',
            'status' => 'active',
            'recorded_by_user_id' => $user->id,
            'reason' => 'Lifecycle fixture.',
        ]);

        return [$user, $organization, $vehicle];
    }
}
