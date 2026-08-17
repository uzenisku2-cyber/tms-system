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

final class DriverPriceListDraftVersionLifecycleTest extends TestCase
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

    public function test_new_draft_version_requires_expected_current_version_and_full_item_set(): void
    {
        [$master, $actor, $priceList] = $this->fixture();

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->postJson(
                $this->versionsUrl($priceList),
                [
                    'expected_current_version' => 1,
                    'valid_from' => '2026-09-01',
                    'valid_until' => null,
                    'change_reason' => 'September rates',
                    'items' => $this->items('12.0000'),
                ],
            );

        $response
            ->assertCreated()
            ->assertJsonPath('data.version_number', 2)
            ->assertJsonPath('data.lock_version', 1)
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas(
            'driver_price_lists',
            [
                'id' => (int) $priceList->getKey(),
                'current_version' => 2,
            ],
        );

        $version = $priceList
            ->versions()
            ->where('version_number', 2)
            ->firstOrFail();

        $this->assertSame(
            4,
            $version->items()->count(),
        );
    }

    public function test_new_draft_version_rejects_stale_expected_current_version_without_write(): void
    {
        [$master, $actor, $priceList] = $this->fixture();

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->postJson(
                $this->versionsUrl($priceList),
                [
                    'expected_current_version' => 2,
                    'valid_from' => '2026-09-01',
                    'valid_until' => null,
                    'change_reason' => 'Stale request',
                    'items' => $this->items('12.0000'),
                ],
            );

        $response->assertStatus(409);

        $this->assertSame(
            1,
            $priceList->versions()->count(),
        );
    }

    public function test_draft_version_update_increments_lock_version_and_replaces_full_item_set(): void
    {
        [$master, $actor, $priceList] = $this->fixture();

        $draft = $this->createDraftVersion(
            $priceList,
            $actor,
        );

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->putJson(
                $this->versionUrl(
                    $priceList,
                    2,
                ),
                [
                    'name' => '  Updated driver compensation  ',
                    'description' => '  Edited through price-list administration  ',
                    'expected_lock_version' => 1,
                    'valid_from' => '2026-09-05',
                    'valid_until' => null,
                    'change_reason' => 'Corrected rates',
                    'items' => $this->items('14.0000'),
                ],
            );

        $response
            ->assertOk()
            ->assertJsonPath('data.version_number', 2)
            ->assertJsonPath('data.lock_version', 2)
            ->assertJsonPath('data.status', 'draft');

        $draft->refresh();
        $priceList->refresh();

        $this->assertSame(
            'Updated driver compensation',
            $priceList->getAttribute('name'),
        );

        $this->assertSame(
            'Edited through price-list administration',
            $priceList->getAttribute('description'),
        );

        $this->assertSame(
            2,
            (int) $draft->getAttribute('lock_version'),
        );

        $this->assertSame(
            4,
            $draft->items()->count(),
        );

        $this->assertDatabaseHas(
            'driver_price_list_items',
            [
                'driver_price_list_version_id' => (int) $draft->getKey(),
                'code' => DriverPriceListItem::CODE_DELIVERED_PARCELS,
                'unit_rate' => '14.0000',
            ],
        );
    }

    public function test_draft_version_update_rejects_stale_lock_version(): void
    {
        [$master, $actor, $priceList] = $this->fixture();

        $draft = $this->createDraftVersion(
            $priceList,
            $actor,
        );

        $draft->forceFill([
            'lock_version' => 2,
        ])->saveOrFail();

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->putJson(
                $this->versionUrl(
                    $priceList,
                    2,
                ),
                [
                    'expected_lock_version' => 1,
                    'valid_from' => '2026-09-05',
                    'valid_until' => null,
                    'change_reason' => 'Stale update',
                    'items' => $this->items('14.0000'),
                ],
            );

        $response->assertStatus(409);

        $this->assertSame(
            2,
            (int) $draft->refresh()->getAttribute(
                'lock_version',
            ),
        );
    }

    /**
     * @return array{Organization, User, DriverPriceList}
     */
    private function fixture(): array
    {
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
            'first_name' => 'Draft',
            'last_name' => 'Version',
            'phone' => null,
            'email' => null,
            'license_number' => 'S022-R06-'.Str::uuid(),
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
            'code' => 'DRV-R06-'.Str::uuid(),
            'name' => 'Driver compensation',
            'description' => null,
            'currency' => 'CZK',
            'status' => DriverPriceList::STATUS_ACTIVE,
            'current_version' => 1,
            'created_by_user_id' => $actor->getKey(),
        ]);

        $version = $priceList->versions()->create([
            'version_number' => 1,
            'lock_version' => 1,
            'status' => DriverPriceListVersion::STATUS_ACTIVE,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'change_reason' => 'Initial',
            'created_by_user_id' => $actor->getKey(),
            'approved_by_user_id' => $actor->getKey(),
            'approved_at' => now(),
            'activated_at' => now(),
        ]);

        $this->replaceItems(
            $version,
            '10.0000',
        );

        return [$master, $actor, $priceList];
    }

    private function createDraftVersion(
        DriverPriceList $priceList,
        User $actor,
    ): DriverPriceListVersion {
        $draft = $priceList->versions()->create([
            'version_number' => 2,
            'lock_version' => 1,
            'status' => DriverPriceListVersion::STATUS_DRAFT,
            'valid_from' => '2026-09-01',
            'valid_until' => null,
            'change_reason' => 'Draft',
            'created_by_user_id' => $actor->getKey(),
        ]);

        $priceList->forceFill([
            'current_version' => 2,
        ])->saveOrFail();

        $this->replaceItems(
            $draft,
            '12.0000',
        );

        return $draft;
    }

    private function replaceItems(
        DriverPriceListVersion $version,
        string $deliveredRate,
    ): void {
        $position = 1;

        foreach ($this->items($deliveredRate) as $item) {
            $code = (string) $item['code'];

            $version->items()->create([
                'code' => $code,
                'description' => null,
                'calculation_method' => DriverPriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE,
                'unit' => $code === DriverPriceListItem::CODE_ACTUAL_KM
                        ? DriverPriceListItem::UNIT_KM
                        : DriverPriceListItem::UNIT_PARCEL,
                'unit_rate' => $item['unit_rate'],
                'currency' => 'CZK',
                'quantity_source' => $code,
                'rounding_scale' => 2,
                'rounding_method' => DriverPriceListItem::ROUNDING_METHOD_HALF_UP,
                'position' => $position,
            ]);

            $position++;
        }
    }

    /**
     * @return list<array{code: string, description: null, unit_rate: string}>
     */
    private function items(
        string $deliveredRate,
    ): array {
        return [
            [
                'code' => DriverPriceListItem::CODE_DELIVERED_PARCELS,
                'description' => null,
                'unit_rate' => $deliveredRate,
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
        ];
    }

    private function versionsUrl(
        DriverPriceList $priceList,
    ): string {
        return '/api/v1/driver-price-lists/'
            .$priceList->getAttribute('public_id')
            .'/versions';
    }

    private function versionUrl(
        DriverPriceList $priceList,
        int $version,
    ): string {
        return $this->versionsUrl($priceList)
            .'/'.$version;
    }
}
