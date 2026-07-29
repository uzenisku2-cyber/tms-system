<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\DailyReports;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
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
