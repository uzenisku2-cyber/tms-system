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

final class DriverPriceListApprovalActivationExpirationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(
            Carbon::parse('2026-08-16 11:00:00'),
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_current_draft_version_can_be_approved_with_matching_lock(): void
    {
        [$master, $actor, $priceList] = $this->fixture();

        $draft = $this->createVersion(
            priceList: $priceList,
            actor: $actor,
            versionNumber: 1,
            status: DriverPriceListVersion::STATUS_DRAFT,
            validFrom: '2026-08-16',
            lockVersion: 2,
        );

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->postJson(
                $this->lifecycleUrl(
                    $priceList,
                    1,
                    'approve',
                ),
                [
                    'expected_lock_version' => 2,
                ],
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                DriverPriceListVersion::STATUS_APPROVED,
            )
            ->assertJsonPath('data.lock_version', 2);

        $draft->refresh();

        $this->assertSame(
            DriverPriceListVersion::STATUS_APPROVED,
            $draft->getAttribute('status'),
        );
        $this->assertSame(
            2,
            (int) $draft->getAttribute('lock_version'),
        );
        $this->assertSame(
            (int) $actor->getKey(),
            (int) $draft->getAttribute('approved_by_user_id'),
        );
        $this->assertNotNull(
            $draft->getAttribute('approved_at'),
        );
        $this->assertNull(
            $draft->getAttribute('activated_at'),
        );
    }

    public function test_stale_lock_rejects_approval_without_mutation(): void
    {
        [$master, $actor, $priceList] = $this->fixture();

        $draft = $this->createVersion(
            priceList: $priceList,
            actor: $actor,
            versionNumber: 1,
            status: DriverPriceListVersion::STATUS_DRAFT,
            validFrom: '2026-08-16',
            lockVersion: 2,
        );

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->postJson(
                $this->lifecycleUrl(
                    $priceList,
                    1,
                    'approve',
                ),
                [
                    'expected_lock_version' => 1,
                ],
            );

        $response->assertStatus(409);

        $draft->refresh();

        $this->assertSame(
            DriverPriceListVersion::STATUS_DRAFT,
            $draft->getAttribute('status'),
        );
        $this->assertNull(
            $draft->getAttribute('approved_at'),
        );
    }

    public function test_approved_version_can_be_activated_and_aggregate_becomes_active(): void
    {
        [$master, $actor, $priceList] = $this->fixture();

        $approved = $this->createVersion(
            priceList: $priceList,
            actor: $actor,
            versionNumber: 1,
            status: DriverPriceListVersion::STATUS_APPROVED,
            validFrom: '2026-08-16',
            lockVersion: 2,
            approved: true,
        );

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->postJson(
                $this->lifecycleUrl(
                    $priceList,
                    1,
                    'activate',
                ),
                [
                    'expected_lock_version' => 2,
                ],
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                DriverPriceListVersion::STATUS_ACTIVE,
            )
            ->assertJsonPath('data.lock_version', 2);

        $approved->refresh();
        $priceList->refresh();

        $this->assertSame(
            DriverPriceListVersion::STATUS_ACTIVE,
            $approved->getAttribute('status'),
        );
        $this->assertNotNull(
            $approved->getAttribute('activated_at'),
        );
        $this->assertSame(
            DriverPriceList::STATUS_ACTIVE,
            $priceList->getAttribute('status'),
        );
    }

    public function test_activation_replaces_previous_active_version_and_closes_its_period(): void
    {
        [$master, $actor, $priceList] = $this->fixture(
            currentVersion: 2,
            priceListStatus: DriverPriceList::STATUS_ACTIVE,
        );

        $old = $this->createVersion(
            priceList: $priceList,
            actor: $actor,
            versionNumber: 1,
            status: DriverPriceListVersion::STATUS_ACTIVE,
            validFrom: '2026-08-01',
            lockVersion: 1,
            approved: true,
            activated: true,
        );

        $replacement = $this->createVersion(
            priceList: $priceList,
            actor: $actor,
            versionNumber: 2,
            status: DriverPriceListVersion::STATUS_APPROVED,
            validFrom: '2026-08-10',
            lockVersion: 3,
            approved: true,
        );

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->postJson(
                $this->lifecycleUrl(
                    $priceList,
                    2,
                    'activate',
                ),
                [
                    'expected_lock_version' => 3,
                ],
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                DriverPriceListVersion::STATUS_ACTIVE,
            );

        $old->refresh();
        $replacement->refresh();

        $this->assertSame(
            DriverPriceListVersion::STATUS_REPLACED,
            $old->getAttribute('status'),
        );
        $this->assertSame(
            '2026-08-09',
            $old->getAttribute('valid_until')->format('Y-m-d'),
        );
        $this->assertSame(
            DriverPriceListVersion::STATUS_ACTIVE,
            $replacement->getAttribute('status'),
        );
        $this->assertSame(
            3,
            (int) $replacement->getAttribute('lock_version'),
        );
    }

    public function test_activation_rejects_replacement_that_does_not_follow_active_start(): void
    {
        [$master, $actor, $priceList] = $this->fixture(
            currentVersion: 2,
            priceListStatus: DriverPriceList::STATUS_ACTIVE,
        );

        $old = $this->createVersion(
            priceList: $priceList,
            actor: $actor,
            versionNumber: 1,
            status: DriverPriceListVersion::STATUS_ACTIVE,
            validFrom: '2026-08-10',
            lockVersion: 1,
            approved: true,
            activated: true,
        );

        $replacement = $this->createVersion(
            priceList: $priceList,
            actor: $actor,
            versionNumber: 2,
            status: DriverPriceListVersion::STATUS_APPROVED,
            validFrom: '2026-08-10',
            lockVersion: 1,
            approved: true,
        );

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->postJson(
                $this->lifecycleUrl(
                    $priceList,
                    2,
                    'activate',
                ),
                [
                    'expected_lock_version' => 1,
                ],
            );

        $response->assertStatus(409);

        $this->assertSame(
            DriverPriceListVersion::STATUS_ACTIVE,
            $old->refresh()->getAttribute('status'),
        );
        $this->assertNull(
            $old->getAttribute('valid_until'),
        );
        $this->assertSame(
            DriverPriceListVersion::STATUS_APPROVED,
            $replacement->refresh()->getAttribute('status'),
        );
    }

    public function test_active_version_can_be_expired_without_changing_aggregate_status_or_current_version(): void
    {
        [$master, $actor, $priceList] = $this->fixture(
            currentVersion: 1,
            priceListStatus: DriverPriceList::STATUS_ACTIVE,
        );

        $active = $this->createVersion(
            priceList: $priceList,
            actor: $actor,
            versionNumber: 1,
            status: DriverPriceListVersion::STATUS_ACTIVE,
            validFrom: '2026-08-01',
            lockVersion: 4,
            approved: true,
            activated: true,
        );

        $approvedAt = $active->getAttribute('approved_at');
        $activatedAt = $active->getAttribute('activated_at');

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->postJson(
                $this->lifecycleUrl(
                    $priceList,
                    1,
                    'expire',
                ),
                [
                    'expected_lock_version' => 4,
                    'valid_until' => '2026-08-15',
                ],
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                DriverPriceListVersion::STATUS_EXPIRED,
            )
            ->assertJsonPath('data.lock_version', 4)
            ->assertJsonPath(
                'data.valid_until',
                '2026-08-15',
            );

        $active->refresh();
        $priceList->refresh();

        $this->assertSame(
            DriverPriceListVersion::STATUS_EXPIRED,
            $active->getAttribute('status'),
        );
        $this->assertSame(
            '2026-08-15',
            $active->getAttribute('valid_until')->format('Y-m-d'),
        );
        $this->assertEquals(
            $approvedAt,
            $active->getAttribute('approved_at'),
        );
        $this->assertEquals(
            $activatedAt,
            $active->getAttribute('activated_at'),
        );
        $this->assertSame(
            DriverPriceList::STATUS_ACTIVE,
            $priceList->getAttribute('status'),
        );
        $this->assertSame(
            1,
            (int) $priceList->getAttribute('current_version'),
        );
    }

    public function test_expiration_date_cannot_be_in_future(): void
    {
        [$master, $actor, $priceList] = $this->fixture(
            currentVersion: 1,
            priceListStatus: DriverPriceList::STATUS_ACTIVE,
        );

        $active = $this->createVersion(
            priceList: $priceList,
            actor: $actor,
            versionNumber: 1,
            status: DriverPriceListVersion::STATUS_ACTIVE,
            validFrom: '2026-08-01',
            lockVersion: 1,
            approved: true,
            activated: true,
        );

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->postJson(
                $this->lifecycleUrl(
                    $priceList,
                    1,
                    'expire',
                ),
                [
                    'expected_lock_version' => 1,
                    'valid_until' => '2026-08-17',
                ],
            );

        $response->assertStatus(409);

        $this->assertSame(
            DriverPriceListVersion::STATUS_ACTIVE,
            $active->refresh()->getAttribute('status'),
        );
        $this->assertNull(
            $active->getAttribute('valid_until'),
        );
    }

    /**
     * @return array{Organization, User, DriverPriceList}
     */
    private function fixture(
        int $currentVersion = 1,
        string $priceListStatus = DriverPriceList::STATUS_DRAFT,
    ): array {
        $master = Organization::query()->create([
            'name' => 'Master carrier',
            'type' => Organization::TYPE_MASTER,
            'status' => Organization::STATUS_ACTIVE,
        ]);

        $actor = User::factory()->create();

        OrganizationMembership::query()->create([
            'organization_id' => $master->getKey(),
            'user_id' => $actor->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
        ]);

        $registrar = app(PermissionRegistrar::class);

        $registrar->setPermissionsTeamId(
            (int) $master->getKey(),
        );

        foreach ([
            DriverSupervisoryAuthorizationService::CURRENT_MANAGE_PERMISSION,
            'compensation.manage',
        ] as $permissionName) {
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

        $driverUser = User::factory()->create();

        $driver = Driver::query()->create([
            'user_id' => $driverUser->getKey(),
            'first_name' => 'Lifecycle',
            'last_name' => 'Driver',
            'phone' => null,
            'email' => null,
            'license_number' => 'S022-R07-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);

        $assignment = DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(),
            'organization_id' => $master->getKey(),
            'employment_type' => DriverOrganizationAssignment::EMPLOYMENT_EMPLOYEE,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'end_reason' => null,
            'created_by_user_id' => $actor->getKey(),
            'ended_by_user_id' => null,
        ]);

        app(DriverSupervisoryScopeService::class)
            ->grantOrganizationScope(
                organization: $master,
                supervisor: $actor,
                targetOrganization: $master,
                createdBy: $actor,
                validFrom: Carbon::parse('2026-08-01'),
            );

        Sanctum::actingAs($actor);

        $priceList = DriverPriceList::query()->create([
            'driver_organization_assignment_id' => $assignment->getKey(),
            'managed_by_organization_id' => $master->getKey(),
            'code' => 'DRV-R07-'.Str::uuid(),
            'name' => 'Driver lifecycle',
            'description' => null,
            'currency' => 'CZK',
            'status' => $priceListStatus,
            'current_version' => $currentVersion,
            'created_by_user_id' => $actor->getKey(),
        ]);

        return [$master, $actor, $priceList];
    }

    private function createVersion(
        DriverPriceList $priceList,
        User $actor,
        int $versionNumber,
        string $status,
        string $validFrom,
        int $lockVersion,
        bool $approved = false,
        bool $activated = false,
    ): DriverPriceListVersion {
        $version = $priceList->versions()->create([
            'version_number' => $versionNumber,
            'lock_version' => $lockVersion,
            'status' => $status,
            'valid_from' => $validFrom,
            'valid_until' => null,
            'change_reason' => 'Lifecycle fixture',
            'created_by_user_id' => $actor->getKey(),
            'approved_by_user_id' => $approved ? $actor->getKey() : null,
            'approved_at' => $approved ? '2026-08-15 10:00:00' : null,
            'activated_at' => $activated ? '2026-08-15 11:00:00' : null,
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

    private function lifecycleUrl(
        DriverPriceList $priceList,
        int $version,
        string $action,
    ): string {
        return '/api/v1/driver-price-lists/'
            .$priceList->getAttribute('public_id')
            .'/versions/'
            .$version
            .'/'
            .$action;
    }
}
