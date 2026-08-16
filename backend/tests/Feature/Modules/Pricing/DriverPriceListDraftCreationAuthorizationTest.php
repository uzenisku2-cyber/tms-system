<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Drivers\Services\DriverSupervisoryScopeService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\DriverPriceList;
use App\Modules\Pricing\Models\DriverPriceListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class DriverPriceListDraftCreationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(
            Carbon::parse('2026-08-16 10:00:00'),
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_own_driver_draft_creation_requires_explicit_scope_and_succeeds_with_it(): void
    {
        $master = $this->createOrganization(
            'Master carrier',
            Organization::TYPE_MASTER,
        );

        $actor = $this->createAuthorizedActor(
            $master,
        );

        $driver = $this->createDriver();

        $assignment = $this->createAssignment(
            driver: $driver,
            organization: $master,
            createdBy: $actor,
        );

        $this->grantOrganizationScope(
            actor: $actor,
            master: $master,
            target: $master,
        );

        $this->actingAsApiUser(
            $actor,
        );

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->postJson(
                '/api/v1/driver-price-lists',
                $this->payload(
                    assignment: $assignment,
                    code: 'DRV-OWN-001',
                ),
            );

        $response->assertCreated();

        $this->assertDatabaseHas(
            'driver_price_lists',
            [
                'driver_organization_assignment_id' => (int) $assignment->getKey(),
                'managed_by_organization_id' => (int) $master->getKey(),
                'code' => 'DRV-OWN-001',
                'status' => DriverPriceList::STATUS_DRAFT,
                'current_version' => 1,
                'created_by_user_id' => (int) $actor->getKey(),
            ],
        );

        $priceListId = (int) DriverPriceList::query()
            ->where('code', 'DRV-OWN-001')
            ->value('id');

        $this->assertGreaterThan(
            0,
            $priceListId,
        );

        $this->assertDatabaseHas(
            'driver_price_list_versions',
            [
                'driver_price_list_id' => $priceListId,
                'version_number' => 1,
                'lock_version' => 1,
                'status' => 'draft',
            ],
        );

        $versionId = (int) DriverPriceList::query()
            ->whereKey($priceListId)
            ->firstOrFail()
            ->versions()
            ->where('version_number', 1)
            ->value('id');

        $this->assertSame(
            4,
            DriverPriceListItem::query()
                ->where(
                    'driver_price_list_version_id',
                    $versionId,
                )
                ->count(),
        );
    }

    public function test_relationship_without_explicit_supervisory_scope_cannot_create_cross_organization_draft(): void
    {
        $master = $this->createOrganization(
            'Master carrier',
            Organization::TYPE_MASTER,
        );

        $subcarrier = $this->createOrganization(
            'Subcarrier',
            Organization::TYPE_CARRIER,
        );

        $actor = $this->createAuthorizedActor(
            $master,
        );

        $driver = $this->createDriver();

        $assignment = $this->createAssignment(
            driver: $driver,
            organization: $subcarrier,
            createdBy: $actor,
        );

        $this->createRelationship(
            source: $master,
            target: $subcarrier,
        );

        $this->actingAsApiUser(
            $actor,
        );

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->postJson(
                '/api/v1/driver-price-lists',
                $this->payload(
                    assignment: $assignment,
                    code: 'DRV-NO-SCOPE',
                ),
            );

        $response->assertNotFound();

        $this->assertDatabaseMissing(
            'driver_price_lists',
            [
                'code' => 'DRV-NO-SCOPE',
            ],
        );
    }

    public function test_cross_organization_draft_succeeds_with_explicit_scope_and_relationship(): void
    {
        $master = $this->createOrganization(
            'Master carrier',
            Organization::TYPE_MASTER,
        );

        $subcarrier = $this->createOrganization(
            'Subcarrier',
            Organization::TYPE_CARRIER,
        );

        $actor = $this->createAuthorizedActor(
            $master,
        );

        $driver = $this->createDriver();

        $assignment = $this->createAssignment(
            driver: $driver,
            organization: $subcarrier,
            createdBy: $actor,
        );

        $relationship = $this->createRelationship(
            source: $master,
            target: $subcarrier,
        );

        $this->grantOrganizationScope(
            actor: $actor,
            master: $master,
            target: $subcarrier,
            relationship: $relationship,
        );

        $this->actingAsApiUser(
            $actor,
        );

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->postJson(
                '/api/v1/driver-price-lists',
                $this->payload(
                    assignment: $assignment,
                    code: 'DRV-SUB-001',
                ),
            );

        $response->assertCreated();

        $this->assertDatabaseHas(
            'driver_price_lists',
            [
                'driver_organization_assignment_id' => (int) $assignment->getKey(),
                'managed_by_organization_id' => (int) $master->getKey(),
                'code' => 'DRV-SUB-001',
                'status' => DriverPriceList::STATUS_DRAFT,
            ],
        );
    }

    public function test_draft_creation_rejects_actor_without_compensation_manage_permission(): void
    {
        $master = $this->createOrganization(
            'Master carrier',
            Organization::TYPE_MASTER,
        );

        $actor = $this->createAuthorizedActor(
            $master,
            grantCompensationPermission: false,
        );

        $driver = $this->createDriver();

        $assignment = $this->createAssignment(
            driver: $driver,
            organization: $master,
            createdBy: $actor,
        );

        $this->grantOrganizationScope(
            actor: $actor,
            master: $master,
            target: $master,
        );

        $this->actingAsApiUser(
            $actor,
        );

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->postJson(
                '/api/v1/driver-price-lists',
                $this->payload(
                    assignment: $assignment,
                    code: 'DRV-NO-COMP',
                ),
            );

        $response->assertForbidden();

        $this->assertDatabaseMissing(
            'driver_price_lists',
            [
                'code' => 'DRV-NO-COMP',
            ],
        );
    }

    private function createOrganization(
        string $name,
        string $type,
    ): Organization {
        return Organization::query()->create([
            'name' => $name,
            'type' => $type,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function createAuthorizedActor(
        Organization $organization,
        bool $grantCompensationPermission = true,
    ): User {
        $actor = User::factory()->create();

        OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(),
            'user_id' => $actor->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
        ]);

        $registrar = app(PermissionRegistrar::class);

        $registrar->setPermissionsTeamId(
            (int) $organization->getKey(),
        );

        $registrar->forgetCachedPermissions();

        $driverPermission = Permission::findOrCreate(
            DriverSupervisoryAuthorizationService::CURRENT_MANAGE_PERMISSION,
            'web',
        );

        $actor->givePermissionTo(
            $driverPermission,
        );

        if ($grantCompensationPermission) {
            $compensationPermission = Permission::findOrCreate(
                'compensation.manage',
                'web',
            );

            $actor->givePermissionTo(
                $compensationPermission,
            );
        }

        $actor->unsetRelation('roles');
        $actor->unsetRelation('permissions');

        $registrar->forgetCachedPermissions();

        return $actor;
    }

    private function actingAsApiUser(
        User $actor,
    ): void {
        Sanctum::actingAs(
            $actor,
        );
    }

    private function createDriver(): Driver
    {
        $user = User::factory()->create();

        return Driver::query()->create([
            'user_id' => $user->getKey(),
            'first_name' => 'Driver',
            'last_name' => 'PriceList',
            'phone' => null,
            'email' => null,
            'license_number' => 'S022-API-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);
    }

    private function createAssignment(
        Driver $driver,
        Organization $organization,
        User $createdBy,
    ): DriverOrganizationAssignment {
        $employmentType = (
            $organization->getAttribute('type')
            === Organization::TYPE_MASTER
        )
            ? DriverOrganizationAssignment::EMPLOYMENT_EMPLOYEE
            : null;

        return DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(),
            'organization_id' => $organization->getKey(),
            'employment_type' => $employmentType,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'end_reason' => null,
            'created_by_user_id' => $createdBy->getKey(),
            'ended_by_user_id' => null,
        ]);
    }

    private function createRelationship(
        Organization $source,
        Organization $target,
    ): OrganizationRelationship {
        return OrganizationRelationship::query()->create([
            'source_organization_id' => $source->getKey(),
            'target_organization_id' => $target->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
        ]);
    }

    private function grantOrganizationScope(
        User $actor,
        Organization $master,
        Organization $target,
        ?OrganizationRelationship $relationship = null,
    ): void {
        app(DriverSupervisoryScopeService::class)
            ->grantOrganizationScope(
                organization: $master,
                supervisor: $actor,
                targetOrganization: $target,
                createdBy: $actor,
                validFrom: Carbon::parse('2026-08-01'),
                organizationRelationship: $relationship,
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(
        DriverOrganizationAssignment $assignment,
        string $code,
    ): array {
        return [
            'driver_organization_assignment_id' => (int) $assignment->getKey(),
            'code' => $code,
            'name' => 'Driver compensation',
            'description' => 'Initial driver compensation draft.',
            'currency' => 'CZK',
            'valid_from' => '2026-08-16',
            'valid_until' => null,
            'change_reason' => 'Initial setup',
            'items' => [
                [
                    'code' => DriverPriceListItem::CODE_DELIVERED_PARCELS,
                    'description' => null,
                    'unit_rate' => '10.0000',
                ],
                [
                    'code' => DriverPriceListItem::CODE_REDIRECTED_PARCELS,
                    'description' => null,
                    'unit_rate' => '5.0000',
                ],
                [
                    'code' => DriverPriceListItem::CODE_UNDELIVERED_PARCELS,
                    'description' => null,
                    'unit_rate' => '0.0000',
                ],
                [
                    'code' => DriverPriceListItem::CODE_ACTUAL_KM,
                    'description' => null,
                    'unit_rate' => '3.5000',
                ],
            ],
        ];
    }
}
