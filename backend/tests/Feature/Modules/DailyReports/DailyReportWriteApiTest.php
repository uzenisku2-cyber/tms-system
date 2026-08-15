<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\DailyReports;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class DailyReportWriteApiTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = '/api/v1/daily-reports';

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_guest_cannot_create_daily_report(): void
    {
        $this->postJson(self::BASE_URL, [])
            ->assertUnauthorized();
    }

    public function test_direct_creation_requires_create_permission(): void
    {
        $organization = $this->createOrganization();

        $user = $this->createMember(
            $organization,
            'driver@example.test',
        );

        $driver = $this->createDriver(
            $user,
            'DIRECT-CREATE',
        );

        $this->authenticate(
            $user,
            $organization,
        );

        $payload = $this->creationPayload(
            (int) $driver->getKey(),
        );

        $this->postJson(
            self::BASE_URL,
            $payload,
        )->assertForbidden();

        $this->grantPermissions(
            $user,
            $organization,
            ['daily-reports.create'],
        );

        $this->authenticate(
            $user,
            $organization,
        );

        $this->postJson(
            self::BASE_URL,
            $payload,
        )
            ->assertCreated()
            ->assertJsonPath(
                'data.status',
                DailyReport::STATUS_DRAFT,
            )
            ->assertJsonPath(
                'data.entry_method',
                DailyReport::ENTRY_METHOD_DRIVER,
            )
            ->assertJsonPath(
                'data.entered_on_behalf',
                false,
            )
            ->assertJsonPath(
                'data.current_version',
                1,
            );
    }

    public function test_delegated_creation_requires_only_enter_for_driver(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createMember(
            $organization,
            'actual-driver@example.test',
        );

        $delegatedUser = $this->createMember(
            $organization,
            'delegated-entry@example.test',
        );

        $driver = $this->createDriver(
            $driverUser,
            'DELEGATED-CREATE',
        );

        $this->grantPermissions(
            $delegatedUser,
            $organization,
            ['daily-reports.enter-for-driver'],
        );

        $this->authenticate(
            $delegatedUser,
            $organization,
        );

        $this->postJson(
            self::BASE_URL,
            $this->creationPayload(
                (int) $driver->getKey(),
            ),
        )
            ->assertCreated()
            ->assertJsonPath(
                'data.entry_method',
                DailyReport::ENTRY_METHOD_DELEGATED,
            )
            ->assertJsonPath(
                'data.entered_on_behalf',
                true,
            )
            ->assertJsonPath(
                'data.entered_by_user_id',
                $delegatedUser->getKey(),
            );
    }

    public function test_trip_and_vehicle_links_are_deferred(): void
    {
        $organization = $this->createOrganization();

        $user = $this->createMember(
            $organization,
            'deferred-links@example.test',
        );

        $driver = $this->createDriver(
            $user,
            'DEFERRED-LINKS',
        );

        $this->grantPermissions(
            $user,
            $organization,
            ['daily-reports.create'],
        );

        $this->authenticate(
            $user,
            $organization,
        );

        $payload = $this->creationPayload(
            (int) $driver->getKey(),
        );

        $payload['trip_id'] = 1;
        $payload['vehicle_id'] = 1;

        $this->postJson(
            self::BASE_URL,
            $payload,
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'trip_id',
                'vehicle_id',
            ]);
    }

    public function test_write_lookup_hides_foreign_report(): void
    {
        $firstOrganization = $this->createOrganization(
            'First organization',
        );

        $firstUser = $this->createMember(
            $firstOrganization,
            'first-driver@example.test',
        );

        $this->createDriver(
            $firstUser,
            'FIRST-DRIVER',
        );

        $this->grantPermissions(
            $firstUser,
            $firstOrganization,
            ['daily-reports.update'],
        );

        $secondOrganization = $this->createOrganization(
            'Second organization',
        );

        $secondUser = $this->createMember(
            $secondOrganization,
            'second-driver@example.test',
        );

        $secondDriver = $this->createDriver(
            $secondUser,
            'SECOND-DRIVER',
        );

        $this->grantPermissions(
            $secondUser,
            $secondOrganization,
            ['daily-reports.create'],
        );

        $this->authenticate(
            $secondUser,
            $secondOrganization,
        );

        $foreignPublicId = (string) $this->postJson(
            self::BASE_URL,
            $this->creationPayload(
                (int) $secondDriver->getKey(),
            ),
        )
            ->assertCreated()
            ->json('data.public_id');

        $this->authenticate(
            $firstUser,
            $firstOrganization,
        );

        $this->patchJson(
            self::BASE_URL.'/'.$foreignPublicId,
            [
                'expected_version' => 1,
                'operational_notes' => 'Invisible update.',
            ],
        )->assertNotFound();
    }

    public function test_complete_write_workflow_is_available_through_api(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createMember(
            $organization,
            'workflow-driver@example.test',
        );

        $reviewer = $this->createMember(
            $organization,
            'workflow-reviewer@example.test',
        );

        $closer = $this->createMember(
            $organization,
            'workflow-closer@example.test',
        );

        $driver = $this->createDriver(
            $driverUser,
            'WORKFLOW-DRIVER',
        );

        $this->grantPermissions(
            $driverUser,
            $organization,
            [
                'daily-reports.create',
                'daily-reports.update',
                'daily-reports.submit',
            ],
        );

        $this->grantPermissions(
            $reviewer,
            $organization,
            [
                'daily-reports.review',
                'daily-reports.request-correction',
                'daily-reports.approve',
            ],
        );

        $this->grantPermissions(
            $closer,
            $organization,
            ['daily-reports.close'],
        );

        $this->authenticate(
            $driverUser,
            $organization,
        );

        $publicId = (string) $this->postJson(
            self::BASE_URL,
            [
                'performed_by_driver_id' => (int) $driver->getKey(),
                'route_number' => 'WORKFLOW-100',
                'service_date' => '2026-07-29',
            ],
        )
            ->assertCreated()
            ->assertJsonPath(
                'data.current_version',
                1,
            )
            ->json('data.public_id');

        $this->patchJson(
            self::BASE_URL.'/'.$publicId,
            [
                'expected_version' => 1,
                'completion_confirmed_at' => '2026-07-29T18:00:00+00:00',
                'delivered_parcels' => 100,
                'redirected_parcels' => 4,
                'undelivered_parcels' => 2,
                'planned_km' => '100.00',
                'actual_km' => '108.00',
                'actual_km_source' => DailyReport::ACTUAL_KM_SOURCE_DELIVERY_APPLICATION,
                'operational_notes' => 'Ready for submission.',
            ],
        )
            ->assertOk()
            ->assertJsonPath(
                'data.current_version',
                2,
            );

        $this->postJson(
            self::BASE_URL.'/'.$publicId.'/submit',
            [
                'expected_version' => 2,
                'reason' => 'Route completed.',
            ],
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                DailyReport::STATUS_SUBMITTED,
            )
            ->assertJsonPath(
                'data.current_version',
                2,
            );

        $this->authenticate(
            $reviewer,
            $organization,
        );

        $this->postJson(
            self::BASE_URL.'/'.$publicId.'/review',
            [
                'expected_version' => 2,
            ],
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                DailyReport::STATUS_UNDER_REVIEW,
            );

        $this->postJson(
            self::BASE_URL.'/'.$publicId.'/request-correction',
            [
                'expected_version' => 2,
                'reason' => 'Clarify operational note.',
            ],
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                DailyReport::STATUS_CORRECTION_REQUESTED,
            );

        $this->authenticate(
            $driverUser,
            $organization,
        );

        $this->postJson(
            self::BASE_URL.'/'.$publicId.'/correct',
            [
                'expected_version' => 2,
                'operational_notes' => 'Correction supplied by the driver.',
                'reason' => 'Dispatcher request resolved.',
            ],
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                DailyReport::STATUS_CORRECTED,
            )
            ->assertJsonPath(
                'data.current_version',
                3,
            );

        $this->postJson(
            self::BASE_URL.'/'.$publicId.'/resubmit',
            [
                'expected_version' => 3,
            ],
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                DailyReport::STATUS_SUBMITTED,
            )
            ->assertJsonPath(
                'data.current_version',
                3,
            );

        $this->authenticate(
            $reviewer,
            $organization,
        );

        $this->postJson(
            self::BASE_URL.'/'.$publicId.'/review',
            [
                'expected_version' => 3,
            ],
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                DailyReport::STATUS_UNDER_REVIEW,
            );

        $this->postJson(
            self::BASE_URL.'/'.$publicId.'/approve',
            [
                'expected_version' => 3,
            ],
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                DailyReport::STATUS_APPROVED,
            )
            ->assertJsonPath(
                'data.current_version',
                3,
            );

        $this->authenticate(
            $closer,
            $organization,
        );

        $this->postJson(
            self::BASE_URL.'/'.$publicId.'/close',
            [
                'expected_version' => 3,
            ],
        )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                DailyReport::STATUS_CLOSED,
            )
            ->assertJsonPath(
                'data.current_version',
                3,
            );

        $this->assertDatabaseHas(
            'daily_reports',
            [
                'public_id' => $publicId,
                'status' => DailyReport::STATUS_CLOSED,
                'current_version' => 3,
            ],
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            3,
        );
    }

    private function createOrganization(
        string $name = 'Write API organization',
    ): Organization {
        return Organization::query()->create([
            'name' => $name,
            'type' => Organization::TYPE_MASTER,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function createMember(
        Organization $organization,
        string $email,
    ): User {
        $user = User::factory()->create([
            'email' => $email,
        ]);

        OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);

        return $user;
    }

    private function createDriver(
        User $user,
        string $licensePrefix,
    ): Driver {
        $driver = Driver::query()->create([
            'user_id' => $user->getKey(),
            'first_name' => 'Write',
            'last_name' => 'Driver',
            'phone' => null,
            'email' => null,
            'license_number' => $licensePrefix.'-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);

        $membership = OrganizationMembership::query()
            ->where('user_id', $user->getKey())
            ->where('status', OrganizationMembership::STATUS_ACTIVE)
            ->whereNull('valid_until')
            ->sole();

        DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(),
            'organization_id' => $membership->getAttribute('organization_id'),
            'employment_type' => DriverOrganizationAssignment::EMPLOYMENT_EMPLOYEE,
            'valid_from' => '2026-07-01',
            'valid_until' => null,
            'end_reason' => null,
            'created_by_user_id' => $user->getKey(),
            'ended_by_user_id' => null,
        ]);

        return $driver;
    }

    /**
     * @param  list<string>  $permissions
     */
    private function grantPermissions(
        User $user,
        Organization $organization,
        array $permissions,
    ): void {
        $registrar = app(PermissionRegistrar::class);

        $previousOrganizationId =
            $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId(
                (int) $organization->getKey(),
            );

            $registrar->forgetCachedPermissions();

            foreach ($permissions as $permissionName) {
                $permission = Permission::findOrCreate(
                    $permissionName,
                    'web',
                );

                $user->givePermissionTo($permission);
            }
        } finally {
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');

            $registrar->setPermissionsTeamId(
                $previousOrganizationId,
            );

            $registrar->forgetCachedPermissions();
        }
    }

    private function authenticate(
        User $user,
        Organization $organization,
    ): void {
        Sanctum::actingAs($user);

        $this->withHeader(
            'X-Organization-ID',
            (string) $organization->getKey(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function creationPayload(int $driverId): array
    {
        return [
            'performed_by_driver_id' => $driverId,
            'route_number' => 'ROUTE-'.Str::uuid(),
            'service_date' => '2026-07-29',
            'completion_confirmed_at' => '2026-07-29T18:00:00+00:00',
            'delivered_parcels' => 100,
            'redirected_parcels' => 4,
            'undelivered_parcels' => 2,
            'planned_km' => '100.00',
            'actual_km' => '108.00',
            'actual_km_source' => DailyReport::ACTUAL_KM_SOURCE_DELIVERY_APPLICATION,
            'operational_notes' => 'API report.',
        ];
    }
}
