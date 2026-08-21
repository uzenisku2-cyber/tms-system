<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\DailyReports;

use App\Models\User;
use App\Modules\DailyReports\Models\DriverQualityProfile;
use App\Modules\DailyReports\Models\DriverQualityProfileBinding;
use App\Modules\DailyReports\Models\DriverQualityProfileComponent;
use App\Modules\DailyReports\Models\DriverQualityProfileVersion;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Drivers\Services\DriverSupervisoryScopeService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class DriverQualityProfileAdministrationApiTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/daily-reports/quality-profiles';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(
            Carbon::parse('2026-08-20 12:00:00'),
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_read_and_write_boundaries_are_enforced_independently(): void
    {
        $this->getJson(self::URL)
            ->assertUnauthorized();

        [$actor, $organization] = $this->context(
            'Boundary organization',
        );

        $this->grantPermissions(
            $actor,
            $organization,
            ['daily-reports.view'],
        );

        Sanctum::actingAs($actor);

        $this->getJson(self::URL)
            ->assertStatus(400);

        $this->organizationRequest(
            $organization,
        )->getJson(self::URL)
            ->assertOk()
            ->assertJsonCount(0, 'data.items');

        $this->organizationRequest(
            $organization,
        )->postJson(
            self::URL,
            $this->profilePayload('BOUNDARY'),
        )->assertForbidden();

        $this->grantPermissions(
            $actor,
            $organization,
            ['daily-reports.review'],
        );

        $this->organizationRequest(
            $organization,
        )->postJson(
            self::URL,
            $this->profilePayload('BOUNDARY'),
        )
            ->assertCreated()
            ->assertJsonPath('data.code', 'BOUNDARY')
            ->assertJsonPath('data.versions.0.status', 'draft');
    }

    public function test_profile_creation_is_atomic_unique_and_organization_scoped(): void
    {
        [$actor, $organization] = $this->authorizedContext(
            'Profile organization',
        );

        $response = $this->createProfile(
            $actor,
            $organization,
            'quality-main',
            [
                DriverQualityProfileComponent::SOURCE_DELIVERED,
                DriverQualityProfileComponent::SOURCE_REDIRECTED,
            ],
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.code', 'QUALITY-MAIN')
            ->assertJsonPath('data.current_version', 1)
            ->assertJsonPath(
                'data.versions.0.denominator_source',
                'loaded_parcels',
            )
            ->assertJsonCount(2, 'data.versions.0.numerator_sources');

        self::assertSame(1, DriverQualityProfile::query()->count());
        self::assertSame(1, DriverQualityProfileVersion::query()->count());
        self::assertSame(2, DriverQualityProfileComponent::query()->count());

        $this->createProfile(
            $actor,
            $organization,
            'QUALITY-MAIN',
            [DriverQualityProfileComponent::SOURCE_DELIVERED],
        )->assertUnprocessable();

        self::assertSame(1, DriverQualityProfile::query()->count());
        self::assertSame(1, DriverQualityProfileVersion::query()->count());

        $this->organizationRequest(
            $organization,
        )->postJson(
            self::URL,
            [
                'code' => 'DISABLED-WITH-SOURCE',
                'name' => 'Invalid disabled profile',
                'calculation_method' => DriverQualityProfileVersion::METHOD_DISABLED,
                'numerator_sources' => [
                    DriverQualityProfileComponent::SOURCE_DELIVERED,
                ],
            ],
        )->assertUnprocessable();

        [, $foreignOrganization] = $this->context(
            'Foreign profile organization',
        );

        $foreignProfile = DriverQualityProfile::query()->create([
            'organization_id' => $foreignOrganization->getKey(),
            'code' => 'FOREIGN',
            'name' => 'Foreign profile',
            'status' => DriverQualityProfile::STATUS_ACTIVE,
            'current_version' => 1,
            'created_by_user_id' => $actor->getKey(),
        ]);

        $this->organizationRequest(
            $organization,
        )->getJson(self::URL.'/'.(string) $foreignProfile->public_id)
            ->assertNotFound();

        $this->organizationRequest(
            $organization,
        )->getJson(self::URL)
            ->assertOk()
            ->assertJsonCount(1, 'data.items');
    }

    public function test_version_lifecycle_is_revision_guarded_and_preserves_history(): void
    {
        [$actor, $organization] = $this->authorizedContext(
            'Lifecycle organization',
        );

        $publicId = $this->profilePublicId(
            $this->createProfile(
                $actor,
                $organization,
                'LIFECYCLE',
                [
                    DriverQualityProfileComponent::SOURCE_DELIVERED,
                    DriverQualityProfileComponent::SOURCE_REDIRECTED,
                ],
            ),
        );

        $this->organizationRequest(
            $organization,
        )->postJson(
            self::URL.'/'.$publicId.'/versions/1/activate',
            [
                'lock_version' => 1,
                'valid_from' => '2026-09-01',
            ],
        )
            ->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.lock_version', 2);

        $this->organizationRequest(
            $organization,
        )->postJson(
            self::URL.'/'.$publicId.'/versions',
            [
                'change_reason' => 'Prepare second formula',
            ],
        )
            ->assertCreated()
            ->assertJsonPath('data.version_number', 2)
            ->assertJsonPath('data.lock_version', 1)
            ->assertJsonCount(2, 'data.numerator_sources');

        $this->organizationRequest(
            $organization,
        )->postJson(
            self::URL.'/'.$publicId.'/versions',
            [],
        )->assertUnprocessable();

        $update = [
            'lock_version' => 1,
            'calculation_method' => DriverQualityProfileVersion::METHOD_DISABLED,
            'numerator_sources' => [],
            'change_reason' => 'Explicitly suppress quality',
        ];

        $this->organizationRequest(
            $organization,
        )->putJson(
            self::URL.'/'.$publicId.'/versions/2',
            $update,
        )
            ->assertOk()
            ->assertJsonPath('data.lock_version', 2)
            ->assertJsonPath(
                'data.calculation_method',
                DriverQualityProfileVersion::METHOD_DISABLED,
            )
            ->assertJsonCount(0, 'data.numerator_sources');

        $this->organizationRequest(
            $organization,
        )->putJson(
            self::URL.'/'.$publicId.'/versions/2',
            $update,
        )->assertUnprocessable();

        $this->organizationRequest(
            $organization,
        )->postJson(
            self::URL.'/'.$publicId.'/versions/2/activate',
            [
                'lock_version' => 2,
                'valid_from' => '2026-10-01',
            ],
        )
            ->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.lock_version', 3);

        $first = DriverQualityProfileVersion::query()
            ->where('version_number', 1)
            ->firstOrFail();
        $second = DriverQualityProfileVersion::query()
            ->where('version_number', 2)
            ->firstOrFail();

        self::assertSame(
            DriverQualityProfileVersion::STATUS_REPLACED,
            $first->status,
        );
        self::assertSame(
            '2026-09-30',
            Carbon::parse((string) $first->valid_until)->format('Y-m-d'),
        );
        self::assertSame(
            DriverQualityProfileVersion::STATUS_ACTIVE,
            $second->status,
        );
        self::assertSame(
            '2026-10-01',
            Carbon::parse((string) $second->valid_from)->format('Y-m-d'),
        );
        self::assertSame(0, $second->components()->count());
    }

    public function test_bindings_resolve_driver_carrier_then_organization(): void
    {
        [$actor, $master] = $this->authorizedContext(
            'Binding master',
        );

        $carrier = $this->organization(
            'Binding carrier',
            Organization::TYPE_CARRIER,
        );

        $relationship = OrganizationRelationship::query()->create([
            'source_organization_id' => $master->getKey(),
            'target_organization_id' => $carrier->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => '2026-01-01 00:00:00',
            'valid_until' => null,
        ]);

        $driverUser = User::factory()->create();
        $driver = Driver::query()->create([
            'user_id' => $driverUser->getKey(),
            'first_name' => 'Scoped',
            'last_name' => 'Driver',
            'license_number' => 'QUALITY-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);

        $assignment = DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(),
            'organization_id' => $carrier->getKey(),
            'employment_type' => DriverOrganizationAssignment::EMPLOYMENT_OTHER,
            'valid_from' => '2026-01-01',
            'valid_until' => null,
            'created_by_user_id' => $actor->getKey(),
        ]);

        app(DriverSupervisoryScopeService::class)
            ->grantOrganizationScope(
                organization: $master,
                supervisor: $actor,
                targetOrganization: $carrier,
                createdBy: $actor,
                validFrom: Carbon::parse('2026-01-01'),
                organizationRelationship: $relationship,
            );

        $organizationProfile = $this->createAndActivate(
            $actor,
            $master,
            'ORG-PROFILE',
            [DriverQualityProfileComponent::SOURCE_DELIVERED],
        );
        $carrierProfile = $this->createAndActivate(
            $actor,
            $master,
            'CARRIER-PROFILE',
            [DriverQualityProfileComponent::SOURCE_REDIRECTED],
        );
        $driverProfile = $this->createAndActivate(
            $actor,
            $master,
            'DRIVER-PROFILE',
            DriverQualityProfileComponent::SOURCES,
        );

        $bindingPayload = static fn (string $profile): array => [
            'profile_public_id' => $profile,
            'valid_from' => '2026-09-01',
        ];

        $this->organizationRequest(
            $master,
        )->putJson(
            self::URL.'/bindings/organization',
            $bindingPayload($organizationProfile),
        )->assertOk();

        $this->organizationRequest(
            $master,
        )->putJson(
            self::URL.'/bindings/carrier-relationships/'
                .$relationship->getKey(),
            $bindingPayload($carrierProfile),
        )->assertOk();

        $this->organizationRequest(
            $master,
        )->putJson(
            self::URL.'/bindings/driver-assignments/'
                .$assignment->getKey(),
            $bindingPayload($driverProfile),
        )->assertOk();

        $driverEffective = $this->effective(
            organization: $master,
            query: [
                'service_date' => '2026-09-15',
                'organization_relationship_id' => $relationship->getKey(),
                'driver_organization_assignment_id' => $assignment->getKey(),
            ],
        );

        self::assertSame(
            DriverQualityProfileBinding::SCOPE_DRIVER_ASSIGNMENT,
            $driverEffective['scope_type'],
        );
        self::assertIsArray($driverEffective['profile']);
        self::assertSame(
            $driverProfile,
            $driverEffective['profile']['public_id'],
        );

        $carrierEffective = $this->effective(
            organization: $master,
            query: [
                'service_date' => '2026-09-15',
                'organization_relationship_id' => $relationship->getKey(),
            ],
        );

        self::assertSame(
            DriverQualityProfileBinding::SCOPE_CARRIER_RELATIONSHIP,
            $carrierEffective['scope_type'],
        );
        self::assertIsArray($carrierEffective['profile']);
        self::assertSame(
            $carrierProfile,
            $carrierEffective['profile']['public_id'],
        );

        $organizationEffective = $this->effective(
            organization: $master,
            query: [
                'service_date' => '2026-09-15',
            ],
        );

        self::assertSame(
            DriverQualityProfileBinding::SCOPE_ORGANIZATION,
            $organizationEffective['scope_type'],
        );
        self::assertIsArray($organizationEffective['profile']);
        self::assertSame(
            $organizationProfile,
            $organizationEffective['profile']['public_id'],
        );

        $this->organizationRequest(
            $master,
        )->deleteJson(
            self::URL.'/bindings/carrier-relationships/'
                .$relationship->getKey(),
            [
                'effective_from' => '2026-10-01',
            ],
        )
            ->assertOk()
            ->assertJsonPath('data.ended', true)
            ->assertJsonPath('data.deleted_binding', false)
            ->assertJsonPath('data.inheritance_from', '2026-10-01');

        $afterCarrierEnd = $this->effective(
            organization: $master,
            query: [
                'service_date' => '2026-10-15',
                'organization_relationship_id' => $relationship->getKey(),
            ],
        );

        self::assertSame(
            DriverQualityProfileBinding::SCOPE_ORGANIZATION,
            $afterCarrierEnd['scope_type'],
        );

        $this->organizationRequest(
            $master,
        )->getJson(self::URL.'/bindings')
            ->assertOk()
            ->assertJsonCount(3, 'data.items');
    }

    public function test_binding_targets_are_source_and_supervisory_scoped(): void
    {
        [$actor, $master] = $this->authorizedContext(
            'Target master',
        );
        $carrier = $this->organization(
            'Visible carrier',
            Organization::TYPE_CARRIER,
        );
        $foreign = $this->organization(
            'Foreign master',
            Organization::TYPE_MASTER,
        );
        $foreignCarrier = $this->organization(
            'Hidden carrier',
            Organization::TYPE_CARRIER,
        );

        $relationship = OrganizationRelationship::query()->create([
            'source_organization_id' => $master->getKey(),
            'target_organization_id' => $carrier->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => '2026-01-01 00:00:00',
            'valid_until' => null,
        ]);
        $hiddenRelationship = OrganizationRelationship::query()->create([
            'source_organization_id' => $foreign->getKey(),
            'target_organization_id' => $foreignCarrier->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => '2026-01-01 00:00:00',
            'valid_until' => null,
        ]);

        $driverUser = User::factory()->create();
        $driver = Driver::query()->create([
            'user_id' => $driverUser->getKey(),
            'first_name' => 'Visible',
            'last_name' => 'Driver',
            'license_number' => 'VISIBLE-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);
        $assignment = DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(),
            'organization_id' => $carrier->getKey(),
            'employment_type' => DriverOrganizationAssignment::EMPLOYMENT_OTHER,
            'valid_from' => '2026-01-01',
            'valid_until' => null,
            'created_by_user_id' => $actor->getKey(),
        ]);

        $hiddenDriverUser = User::factory()->create();
        $hiddenDriver = Driver::query()->create([
            'user_id' => $hiddenDriverUser->getKey(),
            'first_name' => 'Hidden',
            'last_name' => 'Driver',
            'license_number' => 'HIDDEN-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);
        $hiddenAssignment = DriverOrganizationAssignment::query()->create([
            'driver_id' => $hiddenDriver->getKey(),
            'organization_id' => $foreignCarrier->getKey(),
            'employment_type' => DriverOrganizationAssignment::EMPLOYMENT_OTHER,
            'valid_from' => '2026-01-01',
            'valid_until' => null,
            'created_by_user_id' => $actor->getKey(),
        ]);

        app(DriverSupervisoryScopeService::class)
            ->grantOrganizationScope(
                organization: $master,
                supervisor: $actor,
                targetOrganization: $carrier,
                createdBy: $actor,
                validFrom: Carbon::parse('2026-01-01'),
                organizationRelationship: $relationship,
            );

        $response = $this->organizationRequest(
            $master,
        )->getJson(self::URL.'/targets');

        $response->assertOk();
        $data = $response->json('data');
        self::assertIsArray($data);
        $organizationTarget = $data['organization'] ?? null;
        $carrierTargets = $data['carrier_relationships'] ?? null;
        $driverTargets = $data['driver_assignments'] ?? null;
        self::assertIsArray($organizationTarget);
        self::assertIsArray($carrierTargets);
        self::assertIsArray($driverTargets);
        self::assertSame(
            (int) $master->getKey(),
            $organizationTarget['id'],
        );
        self::assertCount(1, $carrierTargets);
        $carrierTarget = $carrierTargets[0] ?? null;
        self::assertIsArray($carrierTarget);
        self::assertSame(
            (int) $relationship->getKey(),
            $carrierTarget['relationship_id'],
        );
        self::assertNotSame(
            (int) $hiddenRelationship->getKey(),
            $carrierTarget['relationship_id'],
        );
        self::assertCount(1, $driverTargets);
        $driverTarget = $driverTargets[0] ?? null;
        self::assertIsArray($driverTarget);
        self::assertSame(
            (int) $assignment->getKey(),
            $driverTarget['assignment_id'],
        );
        self::assertNotSame(
            (int) $hiddenAssignment->getKey(),
            $driverTarget['assignment_id'],
        );
    }

    public function test_foreign_relationship_and_driver_scopes_are_hidden(): void
    {
        [$actor, $organization] = $this->authorizedContext(
            'Scope owner',
        );
        [, $foreign] = $this->context('Foreign scope owner');
        $foreignCarrier = $this->organization(
            'Foreign carrier',
            Organization::TYPE_CARRIER,
        );

        $profile = $this->createAndActivate(
            $actor,
            $organization,
            'SCOPE-SAFE',
            [DriverQualityProfileComponent::SOURCE_DELIVERED],
        );

        $relationship = OrganizationRelationship::query()->create([
            'source_organization_id' => $foreign->getKey(),
            'target_organization_id' => $foreignCarrier->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => '2026-01-01 00:00:00',
            'valid_until' => null,
        ]);

        $driverUser = User::factory()->create();
        $driver = Driver::query()->create([
            'user_id' => $driverUser->getKey(),
            'first_name' => 'Foreign',
            'last_name' => 'Driver',
            'license_number' => 'FOREIGN-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);

        $assignment = DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(),
            'organization_id' => $foreignCarrier->getKey(),
            'employment_type' => DriverOrganizationAssignment::EMPLOYMENT_OTHER,
            'valid_from' => '2026-01-01',
            'valid_until' => null,
            'created_by_user_id' => $actor->getKey(),
        ]);

        $payload = [
            'profile_public_id' => $profile,
            'valid_from' => '2026-09-01',
        ];

        $this->organizationRequest(
            $organization,
        )->putJson(
            self::URL.'/bindings/carrier-relationships/'
                .$relationship->getKey(),
            $payload,
        )->assertNotFound();

        $this->organizationRequest(
            $organization,
        )->putJson(
            self::URL.'/bindings/driver-assignments/'
                .$assignment->getKey(),
            $payload,
        )->assertNotFound();

        self::assertSame(0, DriverQualityProfileBinding::query()->count());
    }

    /** @return array{User, Organization} */
    private function authorizedContext(string $name): array
    {
        [$actor, $organization] = $this->context($name);

        $this->grantPermissions(
            $actor,
            $organization,
            [
                'daily-reports.view',
                'daily-reports.review',
            ],
        );

        Sanctum::actingAs($actor);

        return [$actor, $organization];
    }

    /** @return array{User, Organization} */
    private function context(string $name): array
    {
        $actor = User::factory()->create();
        $organization = $this->organization(
            $name,
            Organization::TYPE_MASTER,
        );

        OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(),
            'user_id' => $actor->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => '2026-01-01',
            'valid_until' => null,
        ]);

        return [$actor, $organization];
    }

    private function organization(
        string $name,
        string $type,
    ): Organization {
        return Organization::query()->create([
            'name' => $name,
            'type' => $type,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    /** @param  list<string>  $permissions */
    private function grantPermissions(
        User $actor,
        Organization $organization,
        array $permissions,
    ): void {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId(
                (int) $organization->getKey(),
            );
            $registrar->forgetCachedPermissions();

            foreach ($permissions as $name) {
                $actor->givePermissionTo(
                    Permission::findOrCreate($name, 'web'),
                );
            }
        } finally {
            $actor->unsetRelation('roles');
            $actor->unsetRelation('permissions');
            $registrar->setPermissionsTeamId($previous);
            $registrar->forgetCachedPermissions();
        }
    }

    /**
     * @param  list<string>  $sources
     */
    private function createProfile(
        User $actor,
        Organization $organization,
        string $code,
        array $sources,
    ): TestResponse {
        Sanctum::actingAs($actor);

        return $this->organizationRequest(
            $organization,
        )->postJson(
            self::URL,
            $this->profilePayload($code, $sources),
        );
    }

    /**
     * @param  list<string>|null  $sources
     * @return array<string, mixed>
     */
    private function profilePayload(
        string $code,
        ?array $sources = null,
    ): array {
        return [
            'code' => $code,
            'name' => 'Profile '.$code,
            'description' => 'API lifecycle test profile',
            'calculation_method' => DriverQualityProfileVersion::METHOD_PROCESSED_SHARE,
            'numerator_sources' => $sources ?? [
                DriverQualityProfileComponent::SOURCE_DELIVERED,
            ],
            'change_reason' => 'Initial profile setup',
        ];
    }

    private function profilePublicId(
        TestResponse $response,
    ): string {
        $response->assertCreated();
        $publicId = $response->json('data.public_id');
        self::assertIsString($publicId);

        return $publicId;
    }

    /** @param  list<string>  $sources */
    private function createAndActivate(
        User $actor,
        Organization $organization,
        string $code,
        array $sources,
    ): string {
        $publicId = $this->profilePublicId(
            $this->createProfile(
                $actor,
                $organization,
                $code,
                $sources,
            ),
        );

        $this->organizationRequest(
            $organization,
        )->postJson(
            self::URL.'/'.$publicId.'/versions/1/activate',
            [
                'lock_version' => 1,
                'valid_from' => '2026-09-01',
            ],
        )->assertOk();

        return $publicId;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function effective(
        Organization $organization,
        array $query,
    ): array {
        $response = $this->organizationRequest(
            $organization,
        )->getJson(
            self::URL.'/effective?'.http_build_query($query),
        );

        $response->assertOk();
        $data = $response->json('data');
        self::assertIsArray($data);

        return $data;
    }

    private function organizationRequest(
        Organization $organization,
    ): static {
        return $this->withHeader(
            'X-Organization-ID',
            (string) $organization->getKey(),
        );
    }
}
