<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\DailyReports;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class DriverPerformanceOverviewApiTest extends TestCase
{
    use RefreshDatabase;

    private const URL =
        '/api/v1/daily-reports/performance-overview';

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_read_boundary_requires_authentication_context_and_permission(): void
    {
        $this->getJson(self::URL)
            ->assertUnauthorized();

        [$user, $organization] = $this->createContext();

        $this->grantViewPermission($user, $organization);

        Sanctum::actingAs($user);

        $this->getJson(self::URL)
            ->assertStatus(400);

        [$unauthorized, $unauthorizedOrganization] =
            $this->createContext('Unauthorized organization');

        Sanctum::actingAs($unauthorized);

        $this->withHeader(
            'X-Organization-ID',
            (string) $unauthorizedOrganization->getKey(),
        )->getJson(self::URL)
            ->assertForbidden();
    }

    public function test_overview_is_organization_scoped_and_uses_weighted_counts(): void
    {
        [$user, $organization, $driver] =
            $this->createContext();

        $this->grantViewPermission($user, $organization);

        $this->createReport(
            organization: $organization,
            user: $user,
            driver: $driver,
            routeNumber: 'PERF-001',
            serviceDate: '2026-07-01',
            status: DailyReport::STATUS_DRAFT,
            entryMethod: DailyReport::ENTRY_METHOD_AUTHORIZED_IMPORT,
            loaded: 100,
            delivered: 70,
            redirected: 20,
            customerRejected: 5,
            plannedKm: '100.00',
            actualKm: '110.00',
        );

        $this->createReport(
            organization: $organization,
            user: $user,
            driver: $driver,
            routeNumber: 'PERF-002',
            serviceDate: '2026-07-02',
            status: DailyReport::STATUS_APPROVED,
            entryMethod: DailyReport::ENTRY_METHOD_DRIVER,
            loaded: 50,
            delivered: 40,
            redirected: 5,
            customerRejected: 0,
            plannedKm: '50.00',
            actualKm: '55.00',
        );

        [$foreignUser, $foreignOrganization, $foreignDriver] =
            $this->createContext('Foreign organization');

        $this->createReport(
            organization: $foreignOrganization,
            user: $foreignUser,
            driver: $foreignDriver,
            routeNumber: 'FOREIGN-001',
            serviceDate: '2026-07-03',
            status: DailyReport::STATUS_DRAFT,
            entryMethod: DailyReport::ENTRY_METHOD_DRIVER,
            loaded: 1000,
            delivered: 1000,
            redirected: 0,
            customerRejected: 0,
            plannedKm: '500.00',
            actualKm: '500.00',
        );

        $data = $this->overview(
            user: $user,
            organization: $organization,
        );

        self::assertSame(
            (int) $organization->getKey(),
            $data['scope']['organization_id'],
        );
        self::assertSame(
            'all_statuses',
            $data['scope']['status_scope'],
        );
        self::assertFalse(
            $data['scope']['financial_eligibility_applied'],
        );
        self::assertSame(2, $data['totals']['route_count']);
        self::assertSame(2, $data['totals']['work_day_count']);
        self::assertSame(150, $data['totals']['loaded_parcels']);
        self::assertSame(110, $data['totals']['delivered_parcels']);
        self::assertSame(25, $data['totals']['redirected_parcels']);
        self::assertSame(
            5,
            $data['totals']['customer_rejected_parcels'],
        );
        self::assertSame(
            140,
            $data['totals']['processed_parcels'],
        );
        self::assertSame(
            10,
            $data['totals']['not_delivered_parcels'],
        );
        self::assertSame(
            93.33,
            $data['totals']['processed_share_percent'],
        );
        self::assertSame(
            16.67,
            $data['totals']['redirected_share_percent'],
        );
        self::assertSame('150.00', $data['totals']['planned_km']);
        self::assertSame('165.00', $data['totals']['actual_km']);
        self::assertSame('15.00', $data['totals']['difference_km']);
        self::assertEquals(
            10.0,
            $data['totals']['kilometre_deviation_percent'],
        );
        self::assertCount(1, $data['drivers']);
        self::assertCount(1, $data['timeline']);
        self::assertSame(
            '2026-07',
            $data['timeline'][0]['period'],
        );
    }

    public function test_driver_date_filter_and_daily_grouping_are_applied(): void
    {
        [$user, $organization, $driver] =
            $this->createContext();

        $secondUser = User::factory()->create();

        $secondDriver = Driver::query()->create([
            'user_id' => $secondUser->getKey(),
            'first_name' => 'Second',
            'last_name' => 'Driver',
            'phone' => null,
            'email' => null,
            'license_number' => 'SECOND-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);

        $this->grantViewPermission($user, $organization);

        foreach ([
            ['2026-07-01', $driver, 'FILTER-001'],
            ['2026-07-02', $driver, 'FILTER-002'],
            ['2026-07-02', $driver, 'FILTER-004'],
            ['2026-07-02', $secondDriver, 'FILTER-003'],
        ] as [$date, $reportDriver, $routeNumber]) {
            $this->createReport(
                organization: $organization,
                user: $user,
                driver: $reportDriver,
                routeNumber: $routeNumber,
                serviceDate: $date,
                status: DailyReport::STATUS_DRAFT,
                entryMethod: DailyReport::ENTRY_METHOD_DRIVER,
                loaded: 10,
                delivered: 8,
                redirected: 1,
                customerRejected: 0,
                plannedKm: '10.00',
                actualKm: '11.00',
            );
        }

        $data = $this->overview(
            user: $user,
            organization: $organization,
            query: http_build_query([
                'performed_by_driver_id' => $driver->getKey(),
                'service_date_from' => '2026-07-02',
                'service_date_to' => '2026-07-02',
                'group_by' => 'day',
            ]),
        );

        self::assertSame(2, $data['totals']['route_count']);
        self::assertSame(1, $data['totals']['work_day_count']);
        self::assertSame(20, $data['totals']['loaded_parcels']);
        self::assertSame(16, $data['totals']['delivered_parcels']);
        self::assertSame(2, $data['totals']['redirected_parcels']);
        self::assertSame(
            (int) $driver->getKey(),
            $data['filters']['performed_by_driver_id'],
        );
        self::assertSame('day', $data['filters']['group_by']);
        self::assertCount(1, $data['drivers']);
        self::assertCount(1, $data['timeline']);
        self::assertSame(
            '2026-07-02',
            $data['timeline'][0]['period'],
        );
    }

    public function test_quick_periods_and_historical_carrier_filters_use_effective_assignment(): void
    {
        CarbonImmutable::setTestNow('2026-08-20 10:00:00');

        try {
            [$user, $organization, $driver] =
                $this->createContext();

            $carrier = Organization::query()->create([
                'name' => 'Historical carrier',
                'type' => Organization::TYPE_CARRIER,
                'status' => Organization::STATUS_ACTIVE,
            ]);

            $secondUser = User::factory()->create();
            $unattributedDriver = Driver::query()->create([
                'user_id' => $secondUser->getKey(),
                'first_name' => 'Without',
                'last_name' => 'Assignment',
                'phone' => null,
                'email' => null,
                'license_number' => 'NO-ASSIGNMENT-'.Str::uuid(),
                'license_category' => 'B',
                'active' => true,
            ]);

            $this->grantViewPermission($user, $organization);

            $this->createAssignment(
                driver: $driver,
                organization: $organization,
                user: $user,
                validFrom: '2026-01-01',
                validUntil: '2026-07-31',
            );

            $this->createAssignment(
                driver: $driver,
                organization: $carrier,
                user: $user,
                validFrom: '2026-08-01',
                validUntil: null,
            );

            foreach ([
                ['2026-07-15', $driver, 'OWN-JULY'],
                ['2026-08-05', $driver, 'CARRIER-AUGUST'],
                ['2026-08-06', $unattributedDriver, 'UNKNOWN-AUGUST'],
            ] as [$date, $reportDriver, $routeNumber]) {
                $this->createReport(
                    organization: $organization,
                    user: $user,
                    driver: $reportDriver,
                    routeNumber: $routeNumber,
                    serviceDate: $date,
                    status: DailyReport::STATUS_DRAFT,
                    entryMethod: DailyReport::ENTRY_METHOD_DRIVER,
                    loaded: 10,
                    delivered: 8,
                    redirected: 1,
                    customerRejected: 0,
                    plannedKm: '10.40',
                    actualKm: '11.60',
                );
            }

            $externalData = $this->overview(
                user: $user,
                organization: $organization,
                query: http_build_query([
                    'period' => 'current_month',
                    'carrier_scope' => 'external',
                    'carrier_organization_id' => $carrier->getKey(),
                ]),
            );

            self::assertSame(
                '2026-08-01',
                $externalData['filters']['service_date_from'],
            );
            self::assertSame(
                '2026-08-31',
                $externalData['filters']['service_date_to'],
            );
            self::assertSame(
                1,
                $externalData['totals']['route_count'],
            );
            self::assertSame(
                'Historical carrier',
                $externalData['drivers'][0]['carriers'][0]['name'],
            );

            /** @var array<string, array<string, mixed>> $periods */
            $periods = [];

            foreach (
                $externalData['filter_options']['quick_periods'] as $quickPeriod
            ) {
                self::assertIsArray($quickPeriod);
                $periods[(string) $quickPeriod['key']] =
                    $quickPeriod;
            }

            self::assertArrayHasKey('current_month', $periods);
            self::assertArrayNotHasKey('previous_month', $periods);
            self::assertSame(
                1,
                $periods['current_month']['route_count'],
            );

            $ownData = $this->overview(
                user: $user,
                organization: $organization,
                query: http_build_query([
                    'period' => 'previous_month',
                    'carrier_scope' => 'own',
                ]),
            );

            self::assertSame(1, $ownData['totals']['route_count']);
            self::assertSame(
                'Vlastní řidiči',
                $ownData['drivers'][0]['carriers'][0]['name'],
            );

            $unattributedData = $this->overview(
                user: $user,
                organization: $organization,
                query: http_build_query([
                    'period' => 'current_month',
                    'carrier_scope' => 'unattributed',
                ]),
            );

            self::assertSame(
                1,
                $unattributedData['totals']['route_count'],
            );
            self::assertSame(
                1,
                $unattributedData['carrier_attribution'][
                    'unattributed_route_count'
                ],
            );

            Sanctum::actingAs($user);

            $this->withHeader(
                'X-Organization-ID',
                (string) $organization->getKey(),
            )->getJson(
                self::URL.'?carrier_scope=external',
            )->assertUnprocessable();
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_zero_loaded_routes_return_null_percentages(): void
    {
        [$user, $organization, $driver] =
            $this->createContext();

        $this->grantViewPermission($user, $organization);

        $this->createReport(
            organization: $organization,
            user: $user,
            driver: $driver,
            routeNumber: 'ZERO-LOADED',
            serviceDate: '2026-07-01',
            status: DailyReport::STATUS_DRAFT,
            entryMethod: DailyReport::ENTRY_METHOD_DRIVER,
            loaded: 0,
            delivered: 0,
            redirected: 0,
            customerRejected: 0,
            plannedKm: '0.00',
            actualKm: '0.00',
        );

        $data = $this->overview(
            user: $user,
            organization: $organization,
        );

        self::assertNull(
            $data['totals']['processed_share_percent'],
        );
        self::assertNull(
            $data['totals']['redirected_share_percent'],
        );
        self::assertNull(
            $data['totals']['kilometre_deviation_percent'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function overview(
        User $user,
        Organization $organization,
        string $query = '',
    ): array {
        Sanctum::actingAs($user);

        $url = self::URL;

        if ($query !== '') {
            $url .= '?'.$query;
        }

        $response = $this->withHeader(
            'X-Organization-ID',
            (string) $organization->getKey(),
        )->getJson($url);

        $response->assertOk();

        $data = $response->json('data');

        self::assertIsArray($data);

        return $data;
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

        return [$user, $organization, $driver];
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
        string $serviceDate,
        string $status,
        string $entryMethod,
        int $loaded,
        int $delivered,
        int $redirected,
        int $customerRejected,
        string $plannedKm,
        string $actualKm,
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
            'service_date' => $serviceDate,
            'status' => $status,
            'entry_method' => $entryMethod,
            'entered_on_behalf' => false,
            'completion_confirmed_at' => null,
            'loaded_parcels' => $loaded,
            'delivered_parcels' => $delivered,
            'redirected_parcels' => $redirected,
            'undelivered_parcels' => $customerRejected,
            'planned_km' => $plannedKm,
            'actual_km' => $actualKm,
            'actual_km_source' => DailyReport::ACTUAL_KM_SOURCE_AUTHORIZED_IMPORT,
            'surcharge_amount' => '0.00',
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

    private function createAssignment(
        Driver $driver,
        Organization $organization,
        User $user,
        string $validFrom,
        ?string $validUntil,
    ): DriverOrganizationAssignment {
        return DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(),
            'organization_id' => $organization->getKey(),
            'employment_type' => DriverOrganizationAssignment::EMPLOYMENT_EMPLOYEE,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'end_reason' => $validUntil === null
                ? null
                : 'Historical assignment ended',
            'created_by_user_id' => $user->getKey(),
            'ended_by_user_id' => $validUntil === null
                ? null
                : $user->getKey(),
        ]);
    }
}
