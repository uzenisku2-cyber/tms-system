<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\DailyReports;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Drivers\Services\DriverRoleProvisioner;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class DailyReportReadApiTest extends TestCase
{
    use RefreshDatabase;

    private const INDEX_URL = '/api/v1/daily-reports';

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_guest_cannot_access_daily_reports(): void
    {
        $this->getJson(self::INDEX_URL)
            ->assertUnauthorized();
    }

    public function test_missing_organization_context_is_rejected(): void
    {
        [$user, $organization] = $this->createContext();

        $this->grantViewPermission(
            $user,
            $organization,
        );

        Sanctum::actingAs($user);

        $this->getJson(self::INDEX_URL)
            ->assertStatus(400);
    }

    public function test_view_permission_is_required(): void
    {
        [$user, $organization] = $this->createContext();

        Sanctum::actingAs($user);

        $this->withHeader(
            'X-Organization-ID',
            (string) $organization->getKey(),
        )->getJson(self::INDEX_URL)
            ->assertForbidden();
    }

    public function test_index_is_filtered_and_organization_scoped(): void
    {
        [$user, $organization, $driver] =
            $this->createContext();

        $this->grantViewPermission(
            $user,
            $organization,
        );

        $draft = $this->createReport(
            organization: $organization,
            user: $user,
            driver: $driver,
            routeNumber: 'ROUTE-DRAFT',
            status: DailyReport::STATUS_DRAFT,
        );

        $submitted = $this->createReport(
            organization: $organization,
            user: $user,
            driver: $driver,
            routeNumber: 'ROUTE-SUBMITTED',
            status: DailyReport::STATUS_SUBMITTED,
        );

        [
            $foreignUser,
            $foreignOrganization,
            $foreignDriver,
        ] = $this->createContext(
            organizationName: 'Foreign organization',
        );

        $foreign = $this->createReport(
            organization: $foreignOrganization,
            user: $foreignUser,
            driver: $foreignDriver,
            routeNumber: 'FOREIGN-SUBMITTED',
            status: DailyReport::STATUS_SUBMITTED,
        );

        Sanctum::actingAs($user);

        $response = $this->withHeader(
            'X-Organization-ID',
            (string) $organization->getKey(),
        )->getJson(
            self::INDEX_URL.
            '?status=submitted'.
            '&sort_by=service_date'.
            '&sort_dir=asc'.
            '&per_page=10',
        );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'public_id' => $submitted->getRouteKey(),
            ])
            ->assertJsonMissing([
                'public_id' => $draft->getRouteKey(),
            ])
            ->assertJsonMissing([
                'public_id' => $foreign->getRouteKey(),
            ]);
    }

    public function test_show_hides_foreign_organization_report(): void
    {
        [$user, $organization, $driver] =
            $this->createContext();

        $this->grantViewPermission(
            $user,
            $organization,
        );

        $visible = $this->createReport(
            organization: $organization,
            user: $user,
            driver: $driver,
            routeNumber: 'VISIBLE-REPORT',
            status: DailyReport::STATUS_SUBMITTED,
        );

        [
            $foreignUser,
            $foreignOrganization,
            $foreignDriver,
        ] = $this->createContext(
            organizationName: 'Foreign organization',
        );

        $foreign = $this->createReport(
            organization: $foreignOrganization,
            user: $foreignUser,
            driver: $foreignDriver,
            routeNumber: 'FOREIGN-REPORT',
            status: DailyReport::STATUS_SUBMITTED,
        );

        Sanctum::actingAs($user);

        $this->withHeader(
            'X-Organization-ID',
            (string) $organization->getKey(),
        )->getJson(
            self::INDEX_URL.'/'.$visible->getRouteKey(),
        )
            ->assertOk()
            ->assertJsonFragment([
                'public_id' => $visible->getRouteKey(),
                'route_number' => 'VISIBLE-REPORT',
            ]);

        $this->withHeader(
            'X-Organization-ID',
            (string) $organization->getKey(),
        )->getJson(
            self::INDEX_URL.'/'.$foreign->getRouteKey(),
        )->assertNotFound();
    }

    public function test_driver_role_cannot_read_another_drivers_reports_or_summary(): void
    {
        [$actor, $organization, $ownDriver] = $this->createContext();
        $otherUser = User::factory()->create();
        $otherDriver = Driver::query()->create([
            'user_id' => $otherUser->getKey(),
            'first_name' => 'Other',
            'last_name' => 'Driver',
            'license_number' => 'API-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);
        $own = $this->createReport($organization, $actor, $ownDriver, 'OWN', DailyReport::STATUS_DRAFT);
        $other = $this->createReport($organization, $otherUser, $otherDriver, 'OTHER', DailyReport::STATUS_DRAFT);

        app(DriverRoleProvisioner::class)->assign($actor, (int) $organization->getKey());
        Sanctum::actingAs($actor);
        $this->withHeader('X-Organization-ID', (string) $organization->getKey());

        $this->getJson(self::INDEX_URL)
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonFragment(['public_id' => $own->getRouteKey()])
            ->assertJsonMissing(['public_id' => $other->getRouteKey()]);
        $this->getJson(self::INDEX_URL.'/'.$own->getRouteKey())->assertOk();
        $this->getJson(self::INDEX_URL.'/'.$other->getRouteKey())->assertNotFound();
        $this->getJson(self::INDEX_URL.'/'.$other->getRouteKey().'/versions')->assertNotFound();
        $this->getJson(self::INDEX_URL.'/'.$other->getRouteKey().'/events')->assertNotFound();
        $this->getJson(self::INDEX_URL.'/performance-overview')->assertForbidden();
        $this->getJson(self::INDEX_URL.'/depot-imports/drafts')->assertForbidden();
        $this->getJson(self::INDEX_URL.'/quality-profiles')->assertForbidden();
    }

    public function test_carrier_reads_only_master_reports_for_its_driver_on_assigned_days(): void
    {
        [$masterUser, $master, $assignedDriver] = $this->createContext();
        [$carrierUser, $carrier] = $this->createContext('Carrier');
        $carrier->update(['type' => Organization::TYPE_SUBCONTRACTOR]);
        [$otherUser, $otherCarrier, $otherDriver] = $this->createContext('Other carrier');
        $otherCarrier->update(['type' => Organization::TYPE_SUBCONTRACTOR]);

        OrganizationRelationship::query()->create([
            'source_organization_id' => $master->getKey(),
            'target_organization_id' => $carrier->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => '2026-01-01',
        ]);
        DriverOrganizationAssignment::query()->create([
            'driver_id' => $assignedDriver->getKey(),
            'organization_id' => $carrier->getKey(),
            'created_by_user_id' => $masterUser->getKey(),
            'valid_from' => '2026-07-01',
            'valid_until' => '2026-07-31',
        ]);
        DriverOrganizationAssignment::query()->create([
            'driver_id' => $otherDriver->getKey(),
            'organization_id' => $otherCarrier->getKey(),
            'created_by_user_id' => $masterUser->getKey(),
            'valid_from' => '2026-07-01',
        ]);
        $visible = $this->createReport($master, $masterUser, $assignedDriver, 'CARRIER-OWN', DailyReport::STATUS_DRAFT);
        $other = $this->createReport($master, $otherUser, $otherDriver, 'OTHER-CARRIER', DailyReport::STATUS_DRAFT);
        $outside = $this->createReport($master, $masterUser, $assignedDriver, 'AFTER-ASSIGNMENT', DailyReport::STATUS_DRAFT);
        $outside->update(['service_date' => '2026-08-01']);

        $this->grantViewPermission($carrierUser, $carrier);
        Sanctum::actingAs($carrierUser);
        $this->withHeader('X-Organization-ID', (string) $carrier->getKey());

        $this->getJson(self::INDEX_URL)
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonFragment(['public_id' => $visible->getRouteKey()])
            ->assertJsonMissing(['public_id' => $other->getRouteKey()])
            ->assertJsonMissing(['public_id' => $outside->getRouteKey()]);
        $this->getJson(self::INDEX_URL.'/'.$visible->getRouteKey())->assertOk();
        $this->getJson(self::INDEX_URL.'/'.$other->getRouteKey())->assertNotFound();
        $this->getJson(self::INDEX_URL.'/'.$outside->getRouteKey())->assertNotFound();
    }

    /**
     * @return array{User, Organization, Driver}
     */
    private function createContext(
        string $organizationName = 'Test organization',
    ): array {
        $user = User::factory()->create();

        $organization = Organization::query()->create([
            'name' => $organizationName,
            'type' => Organization::TYPE_MASTER,
            'status' => Organization::STATUS_ACTIVE,
        ]);

        OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);

        $driver = Driver::query()->create([
            'user_id' => $user->getKey(),
            'first_name' => 'API',
            'last_name' => 'Driver',
            'phone' => null,
            'email' => null,
            'license_number' => 'API-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);

        return [
            $user,
            $organization,
            $driver,
        ];
    }

    private function grantViewPermission(
        User $user,
        Organization $organization,
    ): void {
        $registrar = app(PermissionRegistrar::class);

        $previousOrganizationId =
            $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId(
                (int) $organization->getKey(),
            );

            $registrar->forgetCachedPermissions();

            $permission = Permission::findOrCreate(
                'daily-reports.view',
                'web',
            );

            $user->givePermissionTo($permission);
        } finally {
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');

            $registrar->setPermissionsTeamId(
                $previousOrganizationId,
            );

            $registrar->forgetCachedPermissions();
        }
    }

    private function createReport(
        Organization $organization,
        User $user,
        Driver $driver,
        string $routeNumber,
        string $status,
    ): DailyReport {
        return DailyReport::query()->create([
            'organization_id' => $organization->getKey(),
            'trip_id' => null,
            'performed_by_driver_id' => $driver->getKey(),
            'vehicle_id' => null,
            'entered_by_user_id' => $user->getKey(),
            'route_number' => $routeNumber,
            'route_number_normalized' => mb_strtolower(
                $routeNumber,
                'UTF-8',
            ),
            'service_date' => '2026-07-29',
            'status' => $status,
            'entry_method' => DailyReport::ENTRY_METHOD_DRIVER,
            'entered_on_behalf' => false,
            'completion_confirmed_at' => null,
            'delivered_parcels' => null,
            'redirected_parcels' => null,
            'undelivered_parcels' => null,
            'planned_km' => null,
            'actual_km' => null,
            'actual_km_source' => null,
            'operational_notes' => null,
            'current_version' => 1,
            'submitted_at' => null,
            'review_started_at' => null,
            'reviewed_by_user_id' => null,
            'approved_at' => null,
            'approved_by_user_id' => null,
            'closed_at' => null,
        ]);
    }
}
