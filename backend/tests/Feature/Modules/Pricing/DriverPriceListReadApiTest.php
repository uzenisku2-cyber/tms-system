<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Drivers\Models\DriverSupervisoryScope;
use App\Modules\Drivers\Services\DriverSupervisoryScopeService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\DriverPriceList;
use App\Modules\Pricing\Models\DriverPriceListItem;
use App\Modules\Pricing\Models\DriverPriceListVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class DriverPriceListReadApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(
            Carbon::parse('2026-08-16 12:00:00'),
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_read_routes_require_compensation_view(): void
    {
        $organization = $this->organization('Read permission');
        $actor = $this->actor(
            $organization,
            [],
            grantOwnScope: true,
        );

        $this->withOrganization($organization)
            ->getJson('/api/v1/driver-price-lists')
            ->assertForbidden();
    }

    public function test_compensation_view_does_not_require_users_manage_for_visible_own_driver(): void
    {
        $organization = $this->organization('Read only master');
        $actor = $this->actor(
            $organization,
            ['compensation.view'],
            grantOwnScope: true,
        );

        self::assertFalse($actor->can('users.manage'));

        $assignment = $this->assignment(
            $organization,
            $actor,
            'READ-OWN',
        );

        $priceList = $this->priceList(
            $assignment,
            $organization,
            $actor,
            'Visible own list',
        );

        $this->withOrganization($organization)
            ->getJson('/api/v1/driver-price-lists')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath(
                'data.items.0.public_id',
                $priceList->getAttribute('public_id'),
            );
    }

    public function test_explicit_supervisory_scope_is_required_for_read_visibility(): void
    {
        $organization = $this->organization('No scope master');
        $actor = $this->actor(
            $organization,
            ['compensation.view'],
            grantOwnScope: false,
        );

        $assignment = $this->assignment(
            $organization,
            $actor,
            'READ-NOSCOPE',
        );

        $priceList = $this->priceList(
            $assignment,
            $organization,
            $actor,
            'Hidden without scope',
        );

        $this->withOrganization($organization)
            ->getJson('/api/v1/driver-price-lists')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 0);

        $this->withOrganization($organization)
            ->getJson(
                '/api/v1/driver-price-lists/'
                .$priceList->getAttribute('public_id'),
            )
            ->assertNotFound();
    }

    public function test_master_reads_subcontractor_driver_only_with_active_relationship_and_explicit_scope(): void
    {
        $master = $this->organization('Master');
        $subcontractor = $this->organization(
            'Subcontractor',
            Organization::TYPE_SUBCONTRACTOR,
        );

        $actor = $this->actor(
            $master,
            ['compensation.view'],
            grantOwnScope: false,
        );

        $relationship = OrganizationRelationship::query()->create([
            'source_organization_id' => $master->getKey(),
            'target_organization_id' => $subcontractor->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
        ]);

        $assignment = $this->assignment(
            $subcontractor,
            $actor,
            'READ-SUB',
        );

        $priceList = $this->priceList(
            $assignment,
            $master,
            $actor,
            'Subcontractor list',
        );

        $this->withOrganization($master)
            ->getJson(
                '/api/v1/driver-price-lists/'
                .$priceList->getAttribute('public_id'),
            )
            ->assertNotFound();

        DriverSupervisoryScope::query()->create([
            'organization_id' => $master->getKey(),
            'supervisor_user_id' => $actor->getKey(),
            'scope_type' => DriverSupervisoryScope::TYPE_ORGANIZATION,
            'target_organization_id' => $subcontractor->getKey(),
            'target_driver_id' => null,
            'organization_relationship_id' => $relationship->getKey(),
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'created_by_user_id' => $actor->getKey(),
        ]);

        $this->withOrganization($master)
            ->getJson(
                '/api/v1/driver-price-lists/'
                .$priceList->getAttribute('public_id'),
            )
            ->assertOk()
            ->assertJsonPath(
                'data.public_id',
                $priceList->getAttribute('public_id'),
            );
    }

    public function test_managed_by_boundary_hides_another_organizations_price_list(): void
    {
        $organization = $this->organization('Owning master');
        $other = $this->organization('Other master');

        $actor = $this->actor(
            $organization,
            ['compensation.view'],
            grantOwnScope: true,
        );

        $assignment = $this->assignment(
            $organization,
            $actor,
            'READ-BOUNDARY',
        );

        $foreignManaged = $this->priceList(
            $assignment,
            $other,
            $actor,
            'Foreign managed list',
        );

        $this->withOrganization($organization)
            ->getJson(
                '/api/v1/driver-price-lists/'
                .$foreignManaged->getAttribute('public_id'),
            )
            ->assertNotFound();
    }

    public function test_versions_are_descending_and_version_detail_includes_items(): void
    {
        $organization = $this->organization('Version read master');

        $actor = $this->actor(
            $organization,
            ['compensation.view'],
            grantOwnScope: true,
        );

        $assignment = $this->assignment(
            $organization,
            $actor,
            'READ-VERSIONS',
        );

        $priceList = $this->priceList(
            $assignment,
            $organization,
            $actor,
            'Versioned list',
            currentVersion: 2,
        );

        $this->version(
            $priceList,
            $actor,
            1,
            DriverPriceListVersion::STATUS_REPLACED,
            '2026-08-01',
            '2026-08-09',
        );

        $this->version(
            $priceList,
            $actor,
            2,
            DriverPriceListVersion::STATUS_ACTIVE,
            '2026-08-10',
            null,
        );

        $base = '/api/v1/driver-price-lists/'
            .$priceList->getAttribute('public_id')
            .'/versions';

        $this->withOrganization($organization)
            ->getJson($base)
            ->assertOk()
            ->assertJsonPath(
                'data.items.0.version_number',
                2,
            )
            ->assertJsonPath(
                'data.items.1.version_number',
                1,
            )
            ->assertJsonCount(
                4,
                'data.items.0.items',
            );

        $this->withOrganization($organization)
            ->getJson($base.'/1')
            ->assertOk()
            ->assertJsonPath(
                'data.version_number',
                1,
            )
            ->assertJsonCount(
                4,
                'data.items',
            );

        $this->withOrganization($organization)
            ->getJson($base.'/3')
            ->assertNotFound();
    }

    private function organization(
        string $name,
        string $type = Organization::TYPE_MASTER,
    ): Organization {
        return Organization::query()->create([
            'name' => $name,
            'type' => $type,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function actor(
        Organization $organization,
        array $permissions,
        bool $grantOwnScope,
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
        $previousOrganizationId =
            $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId(
                (int) $organization->getKey(),
            );

            foreach ($permissions as $permissionName) {
                $actor->givePermissionTo(
                    Permission::findOrCreate(
                        $permissionName,
                        'web',
                    ),
                );
            }

            $actor->unsetRelation('roles');
            $actor->unsetRelation('permissions');
            $registrar->forgetCachedPermissions();

            if ($grantOwnScope) {
                app(DriverSupervisoryScopeService::class)
                    ->grantOrganizationScope(
                        organization: $organization,
                        supervisor: $actor,
                        targetOrganization: $organization,
                        createdBy: $actor,
                        validFrom: Carbon::parse('2026-08-01'),
                    );
            }
        } finally {
            $registrar->setPermissionsTeamId(
                $previousOrganizationId,
            );
        }

        Sanctum::actingAs($actor);

        return $actor;
    }

    private function assignment(
        Organization $organization,
        User $actor,
        string $licensePrefix,
    ): DriverOrganizationAssignment {
        $driverUser = User::factory()->create();

        $driver = Driver::query()->create([
            'user_id' => $driverUser->getKey(),
            'first_name' => 'Read',
            'last_name' => 'Driver',
            'phone' => null,
            'email' => null,
            'license_number' => $licensePrefix.'-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);

        return DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(),
            'organization_id' => $organization->getKey(),
            'employment_type' => DriverOrganizationAssignment::EMPLOYMENT_EMPLOYEE,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'end_reason' => null,
            'created_by_user_id' => $actor->getKey(),
            'ended_by_user_id' => null,
        ]);
    }

    private function priceList(
        DriverOrganizationAssignment $assignment,
        Organization $managedBy,
        User $actor,
        string $name,
        int $currentVersion = 1,
    ): DriverPriceList {
        return DriverPriceList::query()->create([
            'driver_organization_assignment_id' => $assignment->getKey(),
            'managed_by_organization_id' => $managedBy->getKey(),
            'code' => 'DRV-READ-'.Str::uuid(),
            'name' => $name,
            'description' => null,
            'currency' => 'CZK',
            'status' => DriverPriceList::STATUS_ACTIVE,
            'current_version' => $currentVersion,
            'created_by_user_id' => $actor->getKey(),
        ]);
    }

    private function version(
        DriverPriceList $priceList,
        User $actor,
        int $versionNumber,
        string $status,
        string $validFrom,
        ?string $validUntil,
    ): DriverPriceListVersion {
        $version = $priceList->versions()->create([
            'version_number' => $versionNumber,
            'lock_version' => 1,
            'status' => $status,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'change_reason' => 'Read fixture',
            'created_by_user_id' => $actor->getKey(),
            'approved_by_user_id' => $actor->getKey(),
            'approved_at' => '2026-07-31 10:00:00',
            'activated_at' => '2026-08-01 00:00:00',
        ]);

        $position = 1;

        foreach (DriverPriceListItem::CODES as $code) {
            $version->items()->create([
                'code' => $code,
                'description' => null,
                'calculation_method' => DriverPriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE,
                'unit' => $code === DriverPriceListItem::CODE_ACTUAL_KM
                        ? DriverPriceListItem::UNIT_KM
                        : DriverPriceListItem::UNIT_PARCEL,
                'unit_rate' => '10.0000',
                'currency' => 'CZK',
                'quantity_source' => $code,
                'rounding_scale' => 2,
                'rounding_method' => DriverPriceListItem::ROUNDING_METHOD_HALF_UP,
                'position' => $position,
            ]);

            $position++;
        }

        return $version;
    }

    private function withOrganization(
        Organization $organization,
    ): self {
        return $this->withHeader(
            'X-Organization-ID',
            (string) $organization->getKey(),
        );
    }
}
