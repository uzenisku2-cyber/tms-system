<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\DailyReports;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Models\DepotImportBatch;
use App\Modules\DailyReports\Models\DepotImportRow;
use App\Modules\DailyReports\Services\DepotDriverRecordReviewService;
use App\Modules\DailyReports\Services\DepotImportIntegrityService;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class DepotDriverRecordReviewApiTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/daily-reports/record-review/depot-driver';

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_record_review_requires_view_and_review_permissions(): void
    {
        [$actor, $organization] = $this->context();
        [$driver, $assignment] = $this->driver(
            $actor,
            $organization,
            'Vít',
            'Hrůza',
        );
        $batch = $this->importedBatch(
            $actor,
            $organization,
            [
                $this->readyRow(
                    10,
                    '2025-06-02',
                    '35',
                    $driver,
                    $assignment,
                    'Hrůza Vít',
                ),
            ],
        );
        Sanctum::actingAs($actor);

        $this->grantPermissions(
            $actor,
            $organization,
            ['daily-reports.view'],
        );

        $this->organizationRequest($organization)
            ->getJson(self::URL.'/'.$batch->getRouteKey())
            ->assertForbidden();

        $this->grantPermissions(
            $actor,
            $organization,
            ['daily-reports.review'],
        );

        $this->organizationRequest($organization)
            ->getJson(self::URL.'/'.$batch->getRouteKey())
            ->assertOk()
            ->assertJsonPath('data.contract.read_only', true)
            ->assertJsonPath(
                'data.summary.missing_driver_record',
                1,
            );
    }

    public function test_review_classifies_matches_differences_missing_records_driver_mismatches_and_no_runs(): void
    {
        [$actor, $organization] = $this->context();
        [$firstDriver, $firstAssignment] = $this->driver(
            $actor,
            $organization,
            'Vít',
            'Hrůza',
        );
        [$secondDriver, $secondAssignment] = $this->driver(
            $actor,
            $organization,
            'Dana',
            'Kökörčená',
        );
        $batch = $this->importedBatch(
            $actor,
            $organization,
            [
                $this->readyRow(
                    10,
                    '2025-06-02',
                    '35',
                    $firstDriver,
                    $firstAssignment,
                    'Hrůza Vít',
                ),
                $this->readyRow(
                    20,
                    '2025-06-03',
                    '36',
                    $firstDriver,
                    $firstAssignment,
                    'Hrůza Vít',
                    [
                        'loaded_parcels' => 50,
                        'delivered_parcels' => 45,
                        'redirected_parcels' => 2,
                        'customer_rejected_parcels' => 1,
                        'computed_not_delivered_parcels' => 2,
                        'actual_km' => '120.00',
                        'planned_km' => '115.00',
                    ],
                ),
                $this->readyRow(
                    30,
                    '2025-06-04',
                    '37',
                    $firstDriver,
                    $firstAssignment,
                    'Hrůza Vít',
                ),
                $this->readyRow(
                    40,
                    '2025-06-05',
                    '38',
                    $firstDriver,
                    $firstAssignment,
                    'Hrůza Vít',
                ),
                $this->noRunRow(
                    50,
                    '2025-06-06',
                    'NO-RUN',
                ),
            ],
        );

        $this->dailyReport(
            $actor,
            $organization,
            $firstDriver,
            '2025-06-02',
            '35',
        );
        $this->dailyReport(
            $actor,
            $organization,
            $firstDriver,
            '2025-06-03',
            '36',
            [
                'loaded_parcels' => 50,
                'delivered_parcels' => 44,
                'redirected_parcels' => 2,
                'undelivered_parcels' => 1,
                'actual_km' => '120.00',
                'planned_km' => '115.00',
            ],
        );
        $this->dailyReport(
            $actor,
            $organization,
            $secondDriver,
            '2025-06-05',
            '38',
        );

        $this->grantPermissions(
            $actor,
            $organization,
            [
                'daily-reports.view',
                'daily-reports.review',
            ],
        );
        Sanctum::actingAs($actor);

        $rowHashesBefore = DepotImportRow::query()
            ->orderBy('source_row')
            ->pluck('protected_values_sha256')
            ->all();
        $totalsHashBefore = (string) $batch->getAttribute(
            'protected_totals_sha256',
        );

        $response = $this->organizationRequest($organization)
            ->getJson(self::URL.'/'.$batch->getRouteKey());

        $response
            ->assertOk()
            ->assertJsonPath('data.workspace', 'depot_driver_record_review')
            ->assertJsonPath('data.batch.status', 'imported')
            ->assertJsonPath('data.batch.source_records_locked', true)
            ->assertJsonPath('data.contract.read_only', true)
            ->assertJsonPath(
                'data.contract.depot_source_integrity_verified',
                true,
            )
            ->assertJsonPath(
                'data.contract.depot_source_values_changed',
                false,
            )
            ->assertJsonPath(
                'data.contract.daily_report_values_changed',
                false,
            )
            ->assertJsonPath(
                'data.contract.reconciliation_records_created',
                0,
            )
            ->assertJsonPath(
                'data.filter_options.comparison_statuses',
                DepotDriverRecordReviewService::COMPARISON_STATUSES,
            )
            ->assertJsonPath(
                'data.filter_options.drivers.0.id',
                $firstDriver->getKey(),
            )
            ->assertJsonPath(
                'data.filter_options.drivers.0.name',
                $firstDriver->full_name,
            )
            ->assertJsonCount(1, 'data.filter_options.drivers')
            ->assertJsonPath('data.summary.source_records', 5)
            ->assertJsonPath('data.summary.matching', 1)
            ->assertJsonPath('data.summary.different', 1)
            ->assertJsonPath('data.summary.missing_driver_record', 1)
            ->assertJsonPath('data.summary.driver_mismatch', 1)
            ->assertJsonPath('data.summary.not_comparable', 1)
            ->assertJsonPath('data.summary.difference_fields', 3)
            ->assertJsonPath('data.pagination.total', 5)
            ->assertJsonPath(
                'data.capabilities.quick_accept_available',
                false,
            )
            ->assertJsonPath(
                'data.capabilities.route_split_available',
                false,
            );

        /** @var list<array<string, mixed>> $items */
        $items = $response->json('data.items');
        $different = collect($items)->firstWhere(
            'comparison_status',
            DepotDriverRecordReviewService::STATUS_DIFFERENT,
        );
        $missing = collect($items)->firstWhere(
            'comparison_status',
            DepotDriverRecordReviewService::STATUS_MISSING_DRIVER_RECORD,
        );
        $mismatch = collect($items)->firstWhere(
            'comparison_status',
            DepotDriverRecordReviewService::STATUS_DRIVER_MISMATCH,
        );
        $noRun = collect($items)->firstWhere(
            'comparison_reason',
            'depot_no_run',
        );

        self::assertIsArray($different);
        self::assertSame(2, $different['difference_count']);
        self::assertSame(
            [
                'delivered_parcels',
                'computed_not_delivered_parcels',
            ],
            collect($different['differences'])
                ->pluck('field')
                ->all(),
        );
        self::assertArrayNotHasKey(
            'reported_not_delivered_parcels',
            $different['depot_record']['values'],
        );
        self::assertIsArray($missing);
        self::assertNull($missing['driver_record']);
        self::assertIsArray($mismatch);
        self::assertSame(1, $mismatch['difference_count']);
        self::assertIsArray($noRun);
        self::assertSame(
            DepotDriverRecordReviewService::STATUS_NOT_COMPARABLE,
            $noRun['comparison_status'],
        );

        self::assertSame(
            $rowHashesBefore,
            DepotImportRow::query()
                ->orderBy('source_row')
                ->pluck('protected_values_sha256')
                ->all(),
        );
        self::assertSame(
            $totalsHashBefore,
            (string) $batch->fresh()?->getAttribute(
                'protected_totals_sha256',
            ),
        );
        self::assertDatabaseCount('depot_import_events', 0);
        self::assertDatabaseCount('daily_report_versions', 0);
        self::assertDatabaseCount('daily_report_events', 0);
    }

    public function test_status_and_business_filters_are_applied_before_pagination(): void
    {
        [$actor, $organization] = $this->context();
        [$driver, $assignment] = $this->driver(
            $actor,
            $organization,
            'Vít',
            'Hrůza',
        );
        $batch = $this->importedBatch(
            $actor,
            $organization,
            [
                $this->readyRow(
                    10,
                    '2025-06-02',
                    '35',
                    $driver,
                    $assignment,
                    'Hrůza Vít',
                ),
                $this->readyRow(
                    20,
                    '2025-06-03',
                    '36-A',
                    $driver,
                    $assignment,
                    'Hrůza Vít',
                ),
                $this->readyRow(
                    30,
                    '2025-06-04',
                    '36-B',
                    $driver,
                    $assignment,
                    'Hrůza Vít',
                ),
            ],
        );
        $this->dailyReport(
            $actor,
            $organization,
            $driver,
            '2025-06-03',
            '36-A',
            ['actual_km' => '999.00'],
        );
        $this->dailyReport(
            $actor,
            $organization,
            $driver,
            '2025-06-04',
            '36-B',
            ['actual_km' => '998.00'],
        );
        $this->grantPermissions(
            $actor,
            $organization,
            [
                'daily-reports.view',
                'daily-reports.review',
            ],
        );
        Sanctum::actingAs($actor);

        $response = $this->organizationRequest($organization)
            ->getJson(
                self::URL.'/'.$batch->getRouteKey().
                '?comparison_status=different'.
                '&performed_by_driver_id='.$driver->getKey().
                '&service_date_from=2025-06-03'.
                '&service_date_to=2025-06-04'.
                '&route_number=36'.
                '&per_page=1'.
                '&page=2',
            );

        $response
            ->assertOk()
            ->assertJsonPath('data.summary.source_records', 2)
            ->assertJsonPath('data.summary.different', 2)
            ->assertJsonPath('data.pagination.current_page', 2)
            ->assertJsonPath('data.pagination.last_page', 2)
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonPath('data.pagination.from', 2)
            ->assertJsonPath('data.pagination.to', 2)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath(
                'data.items.0.comparison_status',
                DepotDriverRecordReviewService::STATUS_DIFFERENT,
            );
    }

    public function test_cancelled_and_foreign_imports_are_excluded_from_record_review(): void
    {
        [$actor, $organization] = $this->context();
        [$driver, $assignment] = $this->driver(
            $actor,
            $organization,
            'Vít',
            'Hrůza',
        );
        $cancelled = $this->importedBatch(
            $actor,
            $organization,
            [
                $this->readyRow(
                    10,
                    '2025-06-02',
                    '35',
                    $driver,
                    $assignment,
                    'Hrůza Vít',
                ),
            ],
        );
        $cancelled->forceFill([
            'status' => DepotImportBatch::STATUS_CANCELLED,
        ])->save();

        [$foreignActor, $foreignOrganization] = $this->context(
            'Cizí dopravce',
        );
        [$foreignDriver, $foreignAssignment] = $this->driver(
            $foreignActor,
            $foreignOrganization,
            'Cizí',
            'Řidič',
        );
        $foreign = $this->importedBatch(
            $foreignActor,
            $foreignOrganization,
            [
                $this->readyRow(
                    10,
                    '2025-06-02',
                    '35',
                    $foreignDriver,
                    $foreignAssignment,
                    'Cizí Řidič',
                ),
            ],
        );

        $this->grantPermissions(
            $actor,
            $organization,
            [
                'daily-reports.view',
                'daily-reports.review',
            ],
        );
        Sanctum::actingAs($actor);

        $this->organizationRequest($organization)
            ->getJson(self::URL.'/'.$cancelled->getRouteKey())
            ->assertNotFound();

        $this->organizationRequest($organization)
            ->getJson(self::URL.'/'.$foreign->getRouteKey())
            ->assertNotFound();
    }

    public function test_source_integrity_failure_stops_comparison(): void
    {
        [$actor, $organization] = $this->context();
        [$driver, $assignment] = $this->driver(
            $actor,
            $organization,
            'Vít',
            'Hrůza',
        );
        $batch = $this->importedBatch(
            $actor,
            $organization,
            [
                $this->readyRow(
                    10,
                    '2025-06-02',
                    '35',
                    $driver,
                    $assignment,
                    'Hrůza Vít',
                ),
            ],
        );
        DepotImportRow::query()->update([
            'loaded_parcels' => 999,
        ]);
        $this->grantPermissions(
            $actor,
            $organization,
            [
                'daily-reports.view',
                'daily-reports.review',
            ],
        );
        Sanctum::actingAs($actor);

        $this->withoutExceptionHandling();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Protected depot-import values changed',
        );

        $this->organizationRequest($organization)
            ->getJson(self::URL.'/'.$batch->getRouteKey());
    }

    /** @return array{User, Organization} */
    private function context(
        string $organizationName = 'Kökörčený',
    ): array {
        $actor = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => $organizationName,
            'type' => Organization::TYPE_MASTER,
            'status' => Organization::STATUS_ACTIVE,
        ]);

        OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(),
            'user_id' => $actor->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => '2025-01-01',
            'valid_until' => null,
        ]);

        return [$actor, $organization];
    }

    /** @return array{Driver, DriverOrganizationAssignment} */
    private function driver(
        User $actor,
        Organization $organization,
        string $firstName,
        string $lastName,
    ): array {
        $driverUser = User::factory()->create();
        $externalId = 'S029-'.Str::upper(Str::random(8));
        $driver = Driver::query()->create([
            'user_id' => $driverUser->getKey(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'external_driver_id' => $externalId,
            'license_number' => 'LIC-'.$externalId,
            'active' => true,
        ]);
        $assignment = DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(),
            'organization_id' => $organization->getKey(),
            'employment_type' => DriverOrganizationAssignment::EMPLOYMENT_EMPLOYEE,
            'valid_from' => '2025-01-01',
            'valid_until' => null,
            'created_by_user_id' => $actor->getKey(),
        ]);

        return [$driver, $assignment];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importedBatch(
        User $actor,
        Organization $organization,
        array $rows,
    ): DepotImportBatch {
        $integrity = app(DepotImportIntegrityService::class);
        $models = collect($rows)->map(
            static fn (array $attributes): DepotImportRow => new DepotImportRow(
                $attributes,
            ),
        );
        $totals = $integrity->totals($models);
        $dates = collect($rows)
            ->pluck('service_date')
            ->sort()
            ->values();
        $readyCount = collect($rows)
            ->where('status', DepotImportRow::STATUS_READY)
            ->count();
        $noRunCount = collect($rows)
            ->where('status', DepotImportRow::STATUS_NO_RUN)
            ->count();
        $batch = DepotImportBatch::query()->create([
            'organization_id' => $organization->getKey(),
            'created_by_user_id' => $actor->getKey(),
            'status' => DepotImportBatch::STATUS_IMPORTED,
            'lock_version' => 7,
            'original_filename' => '06-2025.xlsx',
            'source_sha256' => strtoupper(
                hash('sha256', (string) Str::uuid()),
            ),
            'schema_fingerprint' => strtoupper(
                hash('sha256', 'S029 schema '.Str::uuid()),
            ),
            'sheet_name' => 'List1',
            'header_start_row' => 1,
            'header_end_row' => 2,
            'data_start_row' => 3,
            'confirmed_carrier_alias' => (string) $organization->getAttribute(
                'name',
            ),
            'confirmed_carrier_alias_normalized' => mb_strtolower(
                (string) $organization->getAttribute('name'),
                'UTF-8',
            ),
            'period_from' => (string) $dates->first(),
            'period_until' => (string) $dates->last(),
            'row_count' => count($rows),
            'ready_row_count' => $readyCount,
            'no_run_row_count' => $noRunCount,
            'excluded_carrier_row_count' => 0,
            'source_driver_count' => collect($rows)
                ->pluck('source_driver_key')
                ->unique()
                ->count(),
            'unassigned_ready_row_count' => collect($rows)
                ->where('status', DepotImportRow::STATUS_READY)
                ->whereNull('assigned_driver_id')
                ->count(),
            'source_totals' => $totals,
            'protected_totals_sha256' => $integrity->totalsHash($totals),
        ]);

        foreach ($rows as $attributes) {
            $attributes['depot_import_batch_id'] = $batch->getKey();
            $attributes['protected_values_sha256'] =
                $integrity->protectedRowHash($attributes);
            DepotImportRow::query()->create($attributes);
        }

        return $batch;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function readyRow(
        int $sourceRow,
        string $serviceDate,
        string $routeNumber,
        Driver $driver,
        DriverOrganizationAssignment $assignment,
        string $sourceDriverName,
        array $overrides = [],
    ): array {
        return array_replace(
            [
                'source_row' => $sourceRow,
                'status' => DepotImportRow::STATUS_READY,
                'service_date' => $serviceDate,
                'route_number' => $routeNumber,
                'route_number_normalized' => mb_strtolower(
                    $routeNumber,
                    'UTF-8',
                ),
                'carrier_name' => 'Kökörčený',
                'source_driver_name' => $sourceDriverName,
                'source_driver_key' => mb_strtolower(
                    $sourceDriverName,
                    'UTF-8',
                ),
                'assigned_driver_id' => $driver->getKey(),
                'assigned_driver_organization_assignment_id' => $assignment
                    ->getKey(),
                'departure_time' => '08:00',
                'arrival_time' => '16:00',
                'actual_km' => '164.00',
                'planned_km' => '136.00',
                'loaded_parcels' => 100,
                'delivered_parcels' => 80,
                'redirected_parcels' => 10,
                'customer_rejected_parcels' => 5,
                'computed_not_delivered_parcels' => 5,
                'surcharge_amount' => '0.00',
                'operational_notes' => null,
                'errors' => [],
                'warnings' => [],
            ],
            $overrides,
        );
    }

    /** @return array<string, mixed> */
    private function noRunRow(
        int $sourceRow,
        string $serviceDate,
        string $routeNumber,
    ): array {
        return [
            'source_row' => $sourceRow,
            'status' => DepotImportRow::STATUS_NO_RUN,
            'service_date' => $serviceDate,
            'route_number' => $routeNumber,
            'route_number_normalized' => mb_strtolower(
                $routeNumber,
                'UTF-8',
            ),
            'carrier_name' => 'Kökörčený',
            'source_driver_name' => 'Bez výjezdu',
            'source_driver_key' => 'bez vyjezdu',
            'assigned_driver_id' => null,
            'assigned_driver_organization_assignment_id' => null,
            'departure_time' => null,
            'arrival_time' => null,
            'actual_km' => null,
            'planned_km' => null,
            'loaded_parcels' => null,
            'delivered_parcels' => null,
            'redirected_parcels' => null,
            'customer_rejected_parcels' => null,
            'computed_not_delivered_parcels' => null,
            'surcharge_amount' => null,
            'operational_notes' => 'Bez výjezdu.',
            'errors' => [],
            'warnings' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function dailyReport(
        User $actor,
        Organization $organization,
        Driver $driver,
        string $serviceDate,
        string $routeNumber,
        array $overrides = [],
    ): DailyReport {
        return DailyReport::query()->create(
            array_replace(
                [
                    'organization_id' => $organization->getKey(),
                    'trip_id' => null,
                    'performed_by_driver_id' => $driver->getKey(),
                    'vehicle_id' => null,
                    'entered_by_user_id' => $actor->getKey(),
                    'route_number' => $routeNumber,
                    'route_number_normalized' => mb_strtolower(
                        $routeNumber,
                        'UTF-8',
                    ),
                    'service_date' => $serviceDate,
                    'status' => DailyReport::STATUS_SUBMITTED,
                    'entry_method' => DailyReport::ENTRY_METHOD_DRIVER,
                    'entered_on_behalf' => false,
                    'completion_confirmed_at' => null,
                    'departure_time' => '08:00',
                    'arrival_time' => '16:00',
                    'loaded_parcels' => 100,
                    'delivered_parcels' => 80,
                    'redirected_parcels' => 10,
                    'undelivered_parcels' => 5,
                    'planned_km' => '136.00',
                    'actual_km' => '164.00',
                    'actual_km_source' => DailyReport::ACTUAL_KM_SOURCE_MANUAL,
                    'surcharge_amount' => '0.00',
                    'operational_notes' => null,
                    'current_version' => 1,
                    'submitted_at' => now(),
                    'review_started_at' => null,
                    'reviewed_by_user_id' => null,
                    'approved_at' => null,
                    'approved_by_user_id' => null,
                    'closed_at' => null,
                ],
                $overrides,
            ),
        );
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

            foreach ($permissions as $permission) {
                $actor->givePermissionTo(
                    Permission::findOrCreate($permission, 'web'),
                );
            }
        } finally {
            $actor->unsetRelation('roles');
            $actor->unsetRelation('permissions');
            $registrar->setPermissionsTeamId($previous);
            $registrar->forgetCachedPermissions();
        }
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
