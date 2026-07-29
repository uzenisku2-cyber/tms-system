<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\DailyReports;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReportEvent;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class DailyReportHistoryApiTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = '/api/v1/daily-reports';

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_guest_cannot_access_report_history(): void
    {
        $publicId = (string) Str::uuid();

        $this->getJson(
            self::BASE_URL.'/'.$publicId.'/versions',
        )->assertUnauthorized();

        $this->getJson(
            self::BASE_URL.'/'.$publicId.'/events',
        )->assertUnauthorized();
    }

    public function test_view_permission_is_required_for_history(): void
    {
        $organization = $this->createOrganization();

        $user = $this->createMember(
            $organization,
            'history-no-view@example.test',
        );

        $driver = $this->createDriver(
            $user,
            'HISTORY-NO-VIEW',
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

        $publicId = $this->createReport(
            (int) $driver->getKey(),
        );

        $this->getJson(
            self::BASE_URL.'/'.$publicId.'/versions',
        )->assertForbidden();

        $this->getJson(
            self::BASE_URL.'/'.$publicId.'/events',
        )->assertForbidden();
    }

    public function test_versions_and_events_are_returned_newest_first(): void
    {
        $organization = $this->createOrganization();

        $user = $this->createMember(
            $organization,
            'history-view@example.test',
        );

        $driver = $this->createDriver(
            $user,
            'HISTORY-VIEW',
        );

        $this->grantPermissions(
            $user,
            $organization,
            [
                'daily-reports.create',
                'daily-reports.update',
                'daily-reports.view',
            ],
        );

        $this->authenticate(
            $user,
            $organization,
        );

        $publicId = $this->createReport(
            (int) $driver->getKey(),
        );

        $this->patchJson(
            self::BASE_URL.'/'.$publicId,
            [
                'expected_version' => 1,
                'operational_notes' => 'History update.',
                'reason' => 'Testing immutable history.',
            ],
        )
            ->assertOk()
            ->assertJsonPath(
                'data.current_version',
                2,
            );

        $this->getJson(
            self::BASE_URL.'/'.$publicId.'/versions',
        )
            ->assertOk()
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath(
                'data.items.0.version_number',
                2,
            )
            ->assertJsonPath(
                'data.items.0.snapshot.operational_notes',
                'History update.',
            )
            ->assertJsonPath(
                'data.items.1.version_number',
                1,
            )
            ->assertJsonStructure([
                'data' => [
                    'items' => [
                        '*' => [
                            'version_number',
                            'snapshot',
                            'changed_fields',
                            'created_by_user_id',
                            'change_reason',
                            'created_at',
                        ],
                    ],
                ],
            ]);

        $this->getJson(
            self::BASE_URL.'/'.$publicId.'/events',
        )
            ->assertOk()
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath(
                'data.items.0.event_type',
                DailyReportEvent::TYPE_UPDATED,
            )
            ->assertJsonPath(
                'data.items.1.event_type',
                DailyReportEvent::TYPE_CREATED,
            )
            ->assertJsonStructure([
                'data' => [
                    'items' => [
                        '*' => [
                            'event_type',
                            'from_status',
                            'to_status',
                            'acted_by_user_id',
                            'reason',
                            'affected_fields',
                            'metadata',
                            'created_at',
                        ],
                    ],
                ],
            ]);
    }

    public function test_foreign_report_history_is_hidden(): void
    {
        $foreignOrganization = $this->createOrganization(
            'Foreign history organization',
        );

        $foreignUser = $this->createMember(
            $foreignOrganization,
            'foreign-history@example.test',
        );

        $foreignDriver = $this->createDriver(
            $foreignUser,
            'FOREIGN-HISTORY',
        );

        $this->grantPermissions(
            $foreignUser,
            $foreignOrganization,
            ['daily-reports.create'],
        );

        $this->authenticate(
            $foreignUser,
            $foreignOrganization,
        );

        $foreignPublicId = $this->createReport(
            (int) $foreignDriver->getKey(),
        );

        $visibleOrganization = $this->createOrganization(
            'Visible history organization',
        );

        $visibleUser = $this->createMember(
            $visibleOrganization,
            'visible-history@example.test',
        );

        $this->grantPermissions(
            $visibleUser,
            $visibleOrganization,
            ['daily-reports.view'],
        );

        $this->authenticate(
            $visibleUser,
            $visibleOrganization,
        );

        $this->getJson(
            self::BASE_URL.'/'.$foreignPublicId.'/versions',
        )->assertNotFound();

        $this->getJson(
            self::BASE_URL.'/'.$foreignPublicId.'/events',
        )->assertNotFound();
    }

    private function createOrganization(
        string $name = 'History API organization',
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
        return Driver::query()->create([
            'user_id' => $user->getKey(),
            'first_name' => 'History',
            'last_name' => 'Driver',
            'phone' => null,
            'email' => null,
            'license_number' => $licensePrefix.'-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);
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

    private function createReport(int $driverId): string
    {
        $response = $this->postJson(
            self::BASE_URL,
            [
                'performed_by_driver_id' => $driverId,
                'route_number' => 'HISTORY-'.Str::uuid(),
                'service_date' => '2026-07-29',
            ],
        )->assertCreated();

        $publicId = $response->json('data.public_id');

        $this->assertIsString($publicId);
        $this->assertNotSame('', $publicId);

        return $publicId;
    }
}
