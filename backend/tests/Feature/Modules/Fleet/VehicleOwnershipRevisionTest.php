<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use App\Models\User;
use App\Modules\Fleet\Models\VehicleOwnership;
use App\Modules\Fleet\Models\VehicleRegistryEvent;
use App\Modules\Organizations\Models\Organization;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class VehicleOwnershipRevisionTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $organization = Organization::query()->create(['name' => 'Ownership '.Str::uuid(), 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $actor = User::factory()->create();
        $actor->organizationMemberships()->create(['organization_id' => $organization->id, 'relationship_type' => 'employee', 'status' => 'active', 'valid_from' => now()->subDay()]);
        $this->seed(RolePermissionSeeder::class);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($organization->id);
        $actor->assignRole('super-admin');
        $actor->unsetRelation('roles')->unsetRelation('permissions');
        Sanctum::actingAs($actor);
        $headers = ['X-Organization-ID' => (string) $organization->id];
        $vehicle = $this->withHeaders($headers)->postJson('/api/v1/vehicle-registry-administration', ['registration_number' => 'OWN'.Str::upper(Str::random(7)), 'reason' => 'Ownership test vehicle.'])->assertCreated()->json('vehicle');
        $base = '/api/v1/vehicle-registry-administration/'.$vehicle['public_id'];
        $created = $this->withHeaders($headers)->postJson($base.'/documents', ['expected_revision' => $vehicle['revision'], 'document_type' => 'lease_contract', 'title' => 'Leasing evidence', 'storage_reference' => 'legacy-test-document.pdf', 'access_classification' => 'operational', 'reason' => 'Register ownership evidence.'])->assertCreated()->json();
        $document = $created['document']['public_id'];
        $reviewed = $this->withHeaders($headers)->putJson($base.'/documents/'.$document.'/verification', ['expected_revision' => $created['vehicle']['revision'], 'expected_document_revision' => $created['document']['revision'], 'verification_status' => 'verified', 'reason' => 'Verify ownership evidence.'])->assertOk()->json();
        $data = ['expected_revision' => $reviewed['vehicle']['revision'], 'source_document_public_id' => $document, 'owner_type' => 'external_party', 'external_owner_name' => 'Original owner', 'ownership_share_basis_points' => 10000, 'valid_from' => '2025-06-01', 'valid_until' => null, 'acquisition_basis' => 'other', 'reason' => 'Register original ownership.'];
        $stored = $this->withHeaders($headers)->postJson($base.'/ownerships', $data)->assertCreated()->json();
        $data['expected_revision'] = $stored['vehicle']['revision'];
        $data['expected_ownership_revision'] = $stored['ownership']['revision'];
        $data['external_owner_name'] = 'Corrected owner';
        $data['reason'] = 'Correct owner according to contract.';

        return compact('organization', 'actor', 'headers', 'base', 'stored', 'data');
    }

    public function test_revision_preserves_original_snapshot_and_requires_new_verification(): void
    {
        $f = $this->fixture();
        $ownership = $f['stored']['ownership'];
        $verified = $this->withHeaders($f['headers'])->putJson($f['base'].'/ownerships/'.$ownership['public_id'].'/verification', ['expected_revision' => $f['data']['expected_revision'], 'expected_ownership_revision' => $ownership['revision'], 'source_document_public_id' => $f['data']['source_document_public_id'], 'verification_status' => 'verified', 'reason' => 'Verify original ownership.'])->assertOk()->json();
        $data = [...$f['data'], 'expected_revision' => $verified['vehicle']['revision'], 'expected_ownership_revision' => $verified['ownership']['revision']];
        $eventsBefore = VehicleRegistryEvent::query()->count();
        $result = $this->withHeaders($f['headers'])->putJson($f['base'].'/ownerships/'.$ownership['public_id'].'/revisions', $data)->assertOk()->json();
        self::assertSame('Corrected owner', $result['ownership']['external_owner_name']);
        self::assertSame('unverified', $result['ownership']['verification_status']);
        self::assertSame($verified['ownership']['revision'] + 1, $result['ownership']['revision']);
        self::assertSame($verified['vehicle']['revision'] + 1, $result['vehicle']['revision']);
        self::assertSame(1, VehicleOwnership::query()->count());
        self::assertSame($eventsBefore + 1, VehicleRegistryEvent::query()->count());
        $event = VehicleRegistryEvent::query()->where('event_type', 'vehicle_ownership_evidence_revised')->sole();
        self::assertSame('Original owner', $event->payload['previous_ownership']['external_owner_name']);
        self::assertSame('verified', $event->payload['previous_ownership']['verification_status']);
        self::assertSame('Corrected owner', $event->payload['ownership']['external_owner_name']);
        self::assertSame($data['source_document_public_id'], $event->payload['source_document_public_id']);
    }

    public function test_stale_revisions_do_not_change_ownership_or_history(): void
    {
        $f = $this->fixture();
        $url = $f['base'].'/ownerships/'.$f['stored']['ownership']['public_id'].'/revisions';
        $before = VehicleRegistryEvent::query()->count();
        $this->withHeaders($f['headers'])->putJson($url, [...$f['data'], 'expected_revision' => 999])->assertConflict();
        $this->withHeaders($f['headers'])->putJson($url, [...$f['data'], 'expected_ownership_revision' => 999])->assertConflict();
        self::assertSame($before, VehicleRegistryEvent::query()->count());
        self::assertSame('Original owner', VehicleOwnership::query()->sole()->external_owner_name);
    }

    public function test_unknown_document_and_ownership_are_rejected_without_writes(): void
    {
        $f = $this->fixture();
        $before = VehicleRegistryEvent::query()->count();
        $url = $f['base'].'/ownerships/'.$f['stored']['ownership']['public_id'].'/revisions';
        $this->withHeaders($f['headers'])->putJson($url, [...$f['data'], 'source_document_public_id' => (string) Str::uuid()])->assertNotFound();
        $this->withHeaders($f['headers'])->putJson($f['base'].'/ownerships/'.Str::uuid().'/revisions', $f['data'])->assertNotFound();
        $this->withHeaders($f['headers'])->putJson($url, [...$f['data'], 'reason' => ''])->assertUnprocessable();
        self::assertSame($before, VehicleRegistryEvent::query()->count());
    }

    public function test_driver_cannot_revise_ownership(): void
    {
        $f = $this->fixture();
        $driver = User::factory()->create();
        $driver->organizationMemberships()->create(['organization_id' => $f['organization']->id, 'relationship_type' => 'employee', 'status' => 'active', 'valid_from' => now()->subDay()]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($f['organization']->id);
        $driver->assignRole('driver');
        $driver->unsetRelation('roles')->unsetRelation('permissions');
        Sanctum::actingAs($driver);
        $this->withHeaders($f['headers'])->putJson($f['base'].'/ownerships/'.$f['stored']['ownership']['public_id'].'/revisions', $f['data'])->assertForbidden();
    }

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }
}
