<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\DailyReports;

use App\Models\User;
use App\Modules\DailyReports\Models\DepotImportBatch;
use App\Modules\DailyReports\Models\DepotImportEvent;
use App\Modules\DailyReports\Models\DepotImportRow;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\DepotWorkbookFactory;
use Tests\TestCase;

final class DepotImportDraftApiTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/daily-reports/depot-imports/drafts';

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_draft_creation_requires_write_permission_and_keeps_workbook_out_of_storage(): void
    {
        $path = $this->workbook();

        try {
            [$actor, $organization] = $this->context();
            Sanctum::actingAs($actor);

            $this->organizationRequest($organization)
                ->post(
                    self::URL,
                    $this->draftPayload($path),
                    ['Accept' => 'application/json'],
                )->assertForbidden();

            $this->grantPermissions(
                $actor,
                $organization,
                [
                    'daily-reports.view',
                    'daily-reports.enter-for-driver',
                ],
            );

            $response = $this->organizationRequest($organization)
                ->post(
                    self::URL,
                    $this->draftPayload($path),
                    ['Accept' => 'application/json'],
                );

            $response
                ->assertCreated()
                ->assertJsonPath('data.status', DepotImportBatch::STATUS_DRAFT)
                ->assertJsonPath('data.lock_version', 1)
                ->assertJsonPath('data.source.original_filename', '06-2025.xlsx')
                ->assertJsonPath('data.source.stored', false)
                ->assertJsonPath('data.source.read_only', true)
                ->assertJsonPath(
                    'data.source.formula_values_used_for_import',
                    false,
                )
                ->assertJsonPath('data.counts.rows', 2)
                ->assertJsonPath('data.counts.ready', 2)
                ->assertJsonPath('data.counts.no_run', 0)
                ->assertJsonPath('data.counts.excluded_carrier', 1)
                ->assertJsonPath('data.counts.unassigned_ready', 2)
                ->assertJsonPath('data.source_totals.loaded_parcels', 150)
                ->assertJsonPath('data.source_totals.delivered_parcels', 125)
                ->assertJsonPath('data.source_totals.redirected_parcels', 12)
                ->assertJsonPath(
                    'data.source_totals.customer_rejected_parcels',
                    6,
                )
                ->assertJsonPath(
                    'data.source_totals.computed_not_delivered_parcels',
                    7,
                )
                ->assertJsonPath('data.integrity_verified', true)
                ->assertJsonPath('data.daily_reports_created', 0)
                ->assertJsonPath('data.route_allocations_created', 0)
                ->assertJsonPath('data.finalization_enabled', false)
                ->assertJsonPath('data.cancellation_enabled', false)
                ->assertJsonPath('data.cancellation', null)
                ->assertJsonPath('data.source_records_locked', false)
                ->assertJsonPath('data.reconciliation_available_here', false)
                ->assertJsonCount(2, 'data.rows')
                ->assertJsonMissingPath(
                    'data.rows.0.values.reported_not_delivered_parcels',
                )
                ->assertJsonCount(1, 'data.events');

            self::assertDatabaseCount('depot_import_batches', 1);
            self::assertDatabaseCount('depot_import_rows', 2);
            self::assertDatabaseCount('depot_import_events', 1);
            self::assertDatabaseCount('daily_reports', 0);
            self::assertSame(
                [null, null],
                DepotImportRow::query()
                    ->orderBy('source_row')
                    ->pluck('reported_not_delivered_parcels')
                    ->all(),
            );

            $batch = DepotImportBatch::query()->firstOrFail();

            self::assertSame(
                64,
                strlen(
                    (string) $batch->getAttribute(
                        'protected_totals_sha256',
                    ),
                ),
            );

            $this->organizationRequest($organization)
                ->post(
                    self::URL,
                    $this->draftPayload($path),
                    ['Accept' => 'application/json'],
                )
                ->assertUnprocessable()
                ->assertJsonValidationErrors('workbook');

            self::assertDatabaseCount('depot_import_batches', 1);
        } finally {
            @unlink($path);
        }
    }

    public function test_recent_draft_list_is_organization_scoped_and_can_resume_a_verified_batch(): void
    {
        $path = $this->workbook();

        try {
            [$firstActor, $firstOrganization] = $this->context();
            $this->grantPermissions(
                $firstActor,
                $firstOrganization,
                [
                    'daily-reports.view',
                    'daily-reports.enter-for-driver',
                ],
            );
            Sanctum::actingAs($firstActor);

            $first = $this->organizationRequest($firstOrganization)
                ->post(
                    self::URL,
                    $this->draftPayload($path),
                    ['Accept' => 'application/json'],
                )
                ->assertCreated();
            $firstPublicId = (string) $first->json('data.public_id');

            [$secondActor, $secondOrganization] = $this->context();
            $this->grantPermissions(
                $secondActor,
                $secondOrganization,
                [
                    'daily-reports.view',
                    'daily-reports.enter-for-driver',
                ],
            );
            Sanctum::actingAs($secondActor);

            $second = $this->organizationRequest($secondOrganization)
                ->post(
                    self::URL,
                    $this->draftPayload($path),
                    ['Accept' => 'application/json'],
                )
                ->assertCreated();
            $secondPublicId = (string) $second->json('data.public_id');

            Sanctum::actingAs($firstActor);

            $this->organizationRequest($firstOrganization)
                ->getJson(self::URL)
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.public_id', $firstPublicId)
                ->assertJsonPath('data.0.status', DepotImportBatch::STATUS_DRAFT)
                ->assertJsonPath('data.0.source.original_filename', '06-2025.xlsx')
                ->assertJsonPath('data.0.source.stored', false)
                ->assertJsonPath('data.0.counts.ready', 2)
                ->assertJsonPath('data.0.counts.unassigned_ready', 2)
                ->assertJsonPath('data.0.integrity_verified_on_open', true)
                ->assertJsonPath('data.0.finalization_enabled', false)
                ->assertJsonMissing(['public_id' => $secondPublicId]);

            $this->organizationRequest($firstOrganization)
                ->getJson(self::URL.'/'.$firstPublicId)
                ->assertOk()
                ->assertJsonPath('data.public_id', $firstPublicId)
                ->assertJsonPath('data.integrity_verified', true)
                ->assertJsonPath('data.daily_reports_created', 0)
                ->assertJsonPath('data.finalization_enabled', false);

            $this->organizationRequest($firstOrganization)
                ->getJson(self::URL.'/'.$secondPublicId)
                ->assertNotFound();
        } finally {
            @unlink($path);
        }
    }

    public function test_master_import_lists_and_maps_drivers_of_active_subordinate_carriers_only(): void
    {
        $path = $this->workbook();

        try {
            [$actor, $organization] = $this->context();
            $this->grantPermissions(
                $actor,
                $organization,
                [
                    'daily-reports.view',
                    'daily-reports.enter-for-driver',
                ],
            );
            $ownDriver = $this->eligibleDriver(
                $actor,
                $organization,
                'Dominik',
                'KĂ¶kĂ¶rÄŤenĂ˝',
                'DEPOT-OWN',
            );
            $subordinate = Organization::query()->create([
                'name' => 'VĂ­t HrĹŻza',
                'type' => Organization::TYPE_SUBCONTRACTOR,
                'status' => Organization::STATUS_ACTIVE,
            ]);
            OrganizationRelationship::query()->create([
                'source_organization_id' => $organization->getKey(),
                'target_organization_id' => $subordinate->getKey(),
                'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
                'status' => OrganizationRelationship::STATUS_ACTIVE,
                'valid_from' => '2025-01-01',
                'valid_until' => null,
            ]);
            $subordinateDriver = $this->eligibleDriver(
                $actor,
                $subordinate,
                'VĂ­t',
                'HrĹŻza',
                'DEPOT-SUBORDINATE',
            );
            $unrelated = Organization::query()->create([
                'name' => 'NesouvisejĂ­cĂ­ dopravce',
                'type' => Organization::TYPE_SUBCONTRACTOR,
                'status' => Organization::STATUS_ACTIVE,
            ]);
            $unrelatedDriver = $this->eligibleDriver(
                $actor,
                $unrelated,
                'CizĂ­',
                'ĹidiÄŤ',
                'DEPOT-UNRELATED',
            );
            Sanctum::actingAs($actor);

            $created = $this->organizationRequest($organization)
                ->post(
                    self::URL,
                    $this->draftPayload($path),
                    ['Accept' => 'application/json'],
                )
                ->assertCreated();

            $sourceDriverName = (string) $created->json(
                'data.rows.0.source_driver_name',
            );
            $eligibleDriverIds = collect(
                $created->json('data.eligible_drivers'),
            )->pluck('driver_id')->all();

            self::assertContains($ownDriver->getKey(), $eligibleDriverIds);
            self::assertContains(
                $subordinateDriver->getKey(),
                $eligibleDriverIds,
            );
            self::assertNotContains(
                $unrelatedDriver->getKey(),
                $eligibleDriverIds,
            );

            $batchPublicId = (string) $created->json('data.public_id');

            $this->organizationRequest($organization)
                ->patchJson(
                    self::URL.'/'.$batchPublicId.'/source-driver',
                    [
                        'source_driver_name' => $sourceDriverName,
                        'driver_id' => $subordinateDriver->getKey(),
                        'expected_lock_version' => 1,
                        'reason' => 'PĹ™iĹ™azenĂ­ Ĺ™idiÄŤe podĹ™Ă­zenĂ©ho dopravce.',
                    ],
                )
                ->assertOk()
                ->assertJsonPath(
                    'data.rows.0.assigned_driver.id',
                    $subordinateDriver->getKey(),
                );
        } finally {
            @unlink($path);
        }
    }

    public function test_bulk_name_mapping_and_final_import_preserve_exact_depot_values_without_creating_reports(): void
    {
        $path = $this->workbook();

        try {
            [$actor, $organization] = $this->context();
            $this->grantPermissions(
                $actor,
                $organization,
                [
                    'daily-reports.view',
                    'daily-reports.enter-for-driver',
                ],
            );
            $firstDriver = $this->eligibleDriver(
                $actor,
                $organization,
                'Vít',
                'Hrůza',
                'DEPOT-001',
            );
            Sanctum::actingAs($actor);

            $created = $this->organizationRequest($organization)
                ->post(
                    self::URL,
                    $this->draftPayload($path),
                    ['Accept' => 'application/json'],
                )
                ->assertCreated();

            $batchPublicId = (string) $created->json('data.public_id');
            $rowPublicId = (string) $created->json('data.rows.0.public_id');
            $totalsHash = (string) $created->json(
                'data.protected_totals_sha256',
            );
            $sourceTotals = $created->json('data.source_totals');
            $rowHashesBefore = DepotImportRow::query()
                ->orderBy('source_row')
                ->pluck('protected_values_sha256')
                ->all();
            $valuesBefore = DepotImportRow::query()
                ->orderBy('source_row')
                ->get([
                    'source_row',
                    'loaded_parcels',
                    'delivered_parcels',
                    'redirected_parcels',
                    'customer_rejected_parcels',
                    'computed_not_delivered_parcels',
                    'actual_km',
                    'planned_km',
                ])
                ->toArray();

            $mapped = $this->organizationRequest($organization)
                ->patchJson(
                    self::URL.'/'.$batchPublicId.'/source-driver',
                    [
                        'source_driver_name' => ' Hrůza Vít ',
                        'driver_id' => $firstDriver->getKey(),
                        'expected_lock_version' => 1,
                        'reason' => 'Potvrzené hromadné mapování před importem.',
                    ],
                );

            $mapped
                ->assertOk()
                ->assertJsonPath('data.status', DepotImportBatch::STATUS_READY)
                ->assertJsonPath('data.lock_version', 2)
                ->assertJsonPath('data.counts.unassigned_ready', 0)
                ->assertJsonPath('data.finalization_enabled', true)
                ->assertJsonPath('data.cancellation_enabled', false)
                ->assertJsonPath('data.source_records_locked', false)
                ->assertJsonPath('data.protected_totals_sha256', $totalsHash)
                ->assertJsonPath('data.source_totals', $sourceTotals)
                ->assertJsonPath(
                    'data.rows.0.assigned_driver.id',
                    $firstDriver->getKey(),
                )
                ->assertJsonPath(
                    'data.rows.1.assigned_driver.id',
                    $firstDriver->getKey(),
                );

            $this->organizationRequest($organization)
                ->patchJson(
                    self::URL.'/'.$batchPublicId.'/rows/'.$rowPublicId.'/driver',
                    [
                        'driver_id' => $firstDriver->getKey(),
                        'expected_lock_version' => 2,
                        'reason' => 'Import nesmí umožnit jednotlivé přeřazení.',
                    ],
                )
                ->assertNotFound();

            $imported = $this->organizationRequest($organization)
                ->postJson(
                    self::URL.'/'.$batchPublicId.'/finalize',
                    [
                        'expected_lock_version' => 2,
                        'reason' => 'Potvrzení přesného importu zápisu depa.',
                    ],
                );

            $imported
                ->assertOk()
                ->assertJsonPath('data.status', DepotImportBatch::STATUS_IMPORTED)
                ->assertJsonPath('data.lock_version', 3)
                ->assertJsonPath('data.counts.unassigned_ready', 0)
                ->assertJsonPath('data.finalization_enabled', false)
                ->assertJsonPath('data.cancellation_enabled', true)
                ->assertJsonPath('data.cancellation', null)
                ->assertJsonPath('data.source_records_locked', true)
                ->assertJsonPath('data.reconciliation_available_here', false)
                ->assertJsonPath('data.daily_reports_created', 0)
                ->assertJsonPath('data.route_allocations_created', 0)
                ->assertJsonPath('data.protected_totals_sha256', $totalsHash)
                ->assertJsonPath('data.source_totals', $sourceTotals)
                ->assertJsonPath(
                    'data.rows.0.assigned_driver.id',
                    $firstDriver->getKey(),
                )
                ->assertJsonPath(
                    'data.rows.1.assigned_driver.id',
                    $firstDriver->getKey(),
                );

            $this->organizationRequest($organization)
                ->postJson(
                    self::URL.'/'.$batchPublicId.'/cancel',
                    [
                        'expected_lock_version' => 3,
                        'reason' => 'Dávka byla nahrána omylem.',
                    ],
                )
                ->assertForbidden();

            $this->grantPermissions(
                $actor,
                $organization,
                ['daily-reports.review'],
            );

            $cancelled = $this->organizationRequest($organization)
                ->postJson(
                    self::URL.'/'.$batchPublicId.'/cancel',
                    [
                        'expected_lock_version' => 3,
                        'reason' => 'Dávka byla nahrána omylem.',
                    ],
                );

            $cancelled
                ->assertOk()
                ->assertJsonPath(
                    'data.status',
                    DepotImportBatch::STATUS_CANCELLED,
                )
                ->assertJsonPath('data.lock_version', 4)
                ->assertJsonPath('data.finalization_enabled', false)
                ->assertJsonPath('data.cancellation_enabled', false)
                ->assertJsonPath('data.source_records_locked', true)
                ->assertJsonPath(
                    'data.cancellation.reason',
                    'Dávka byla nahrána omylem.',
                )
                ->assertJsonPath(
                    'data.cancellation.acted_by_user_id',
                    $actor->getKey(),
                )
                ->assertJsonPath('data.protected_totals_sha256', $totalsHash)
                ->assertJsonPath('data.source_totals', $sourceTotals)
                ->assertJsonPath('data.daily_reports_created', 0)
                ->assertJsonPath('data.route_allocations_created', 0);

            self::assertNotEmpty(
                $cancelled->json('data.cancellation.created_at'),
            );

            $this->organizationRequest($organization)
                ->postJson(
                    self::URL.'/'.$batchPublicId.'/cancel',
                    [
                        'expected_lock_version' => 4,
                        'reason' => 'Opakované storno nesmí být povoleno.',
                    ],
                )
                ->assertUnprocessable()
                ->assertJsonValidationErrors('batch');

            self::assertSame(
                $rowHashesBefore,
                DepotImportRow::query()
                    ->orderBy('source_row')
                    ->pluck('protected_values_sha256')
                    ->all(),
            );
            self::assertSame(
                $valuesBefore,
                DepotImportRow::query()
                    ->orderBy('source_row')
                    ->get([
                        'source_row',
                        'loaded_parcels',
                        'delivered_parcels',
                        'redirected_parcels',
                        'customer_rejected_parcels',
                        'computed_not_delivered_parcels',
                        'actual_km',
                        'planned_km',
                    ])
                    ->toArray(),
            );
            self::assertSame(
                [
                    DepotImportEvent::TYPE_DRAFT_CREATED,
                    DepotImportEvent::TYPE_SOURCE_DRIVER_MAPPED,
                    DepotImportEvent::TYPE_IMPORT_FINALIZED,
                    DepotImportEvent::TYPE_IMPORT_CANCELLED,
                ],
                DepotImportEvent::query()
                    ->orderBy('id')
                    ->pluck('event_type')
                    ->all(),
            );
            self::assertDatabaseCount('daily_reports', 0);

            $this->organizationRequest($organization)
                ->patchJson(
                    self::URL.'/'.$batchPublicId.'/source-driver',
                    [
                        'source_driver_name' => 'Hrůza Vít',
                        'driver_id' => $firstDriver->getKey(),
                        'expected_lock_version' => 4,
                        'reason' => 'Pokus změnit uzamčený import.',
                    ],
                )
                ->assertUnprocessable()
                ->assertJsonValidationErrors('batch');

            self::assertSame(
                4,
                (int) DepotImportBatch::query()
                    ->firstOrFail()
                    ->getAttribute('lock_version'),
            );
        } finally {
            @unlink($path);
        }
    }

    public function test_final_import_rejects_unassigned_rows_and_stale_lock_versions(): void
    {
        $path = $this->workbook();

        try {
            [$actor, $organization] = $this->context();
            $this->grantPermissions(
                $actor,
                $organization,
                [
                    'daily-reports.view',
                    'daily-reports.enter-for-driver',
                ],
            );
            $driver = $this->eligibleDriver(
                $actor,
                $organization,
                'Vít',
                'Hrůza',
                'DEPOT-001',
            );
            Sanctum::actingAs($actor);

            $created = $this->organizationRequest($organization)
                ->post(
                    self::URL,
                    $this->draftPayload($path),
                    ['Accept' => 'application/json'],
                )
                ->assertCreated();
            $batchPublicId = (string) $created->json('data.public_id');

            $this->organizationRequest($organization)
                ->postJson(
                    self::URL.'/'.$batchPublicId.'/finalize',
                    [
                        'expected_lock_version' => 1,
                        'reason' => 'Předčasný pokus.',
                    ],
                )
                ->assertUnprocessable()
                ->assertJsonValidationErrors('batch');

            $this->organizationRequest($organization)
                ->patchJson(
                    self::URL.'/'.$batchPublicId.'/source-driver',
                    [
                        'source_driver_name' => 'Hrůza Vít',
                        'driver_id' => $driver->getKey(),
                        'expected_lock_version' => 1,
                        'reason' => 'Hromadné přiřazení.',
                    ],
                )
                ->assertOk();

            $this->organizationRequest($organization)
                ->postJson(
                    self::URL.'/'.$batchPublicId.'/finalize',
                    [
                        'expected_lock_version' => 1,
                        'reason' => 'Zastaralé potvrzení.',
                    ],
                )
                ->assertUnprocessable()
                ->assertJsonValidationErrors('expected_lock_version');

            self::assertSame(
                DepotImportBatch::STATUS_READY,
                DepotImportBatch::query()
                    ->firstOrFail()
                    ->getAttribute('status'),
            );
            self::assertDatabaseCount('daily_reports', 0);
        } finally {
            @unlink($path);
        }
    }

    public function test_driver_must_be_active_and_assigned_to_the_main_carrier_on_every_affected_route_date(): void
    {
        $path = $this->workbook();

        try {
            [$actor, $organization] = $this->context();
            $this->grantPermissions(
                $actor,
                $organization,
                [
                    'daily-reports.view',
                    'daily-reports.enter-for-driver',
                ],
            );
            $lateDriver = $this->eligibleDriver(
                $actor,
                $organization,
                'Pozdní',
                'Řidič',
                'DEPOT-LATE',
                '2025-06-03',
            );
            Sanctum::actingAs($actor);

            $created = $this->organizationRequest($organization)
                ->post(
                    self::URL,
                    $this->draftPayload($path),
                    ['Accept' => 'application/json'],
                )
                ->assertCreated();

            $batchPublicId = (string) $created->json('data.public_id');

            $this->organizationRequest($organization)
                ->patchJson(
                    self::URL.'/'.$batchPublicId.'/source-driver',
                    [
                        'source_driver_name' => 'Hrůza Vít',
                        'driver_id' => $lateDriver->getKey(),
                        'expected_lock_version' => 1,
                        'reason' => 'Neplatný pokus o mapování.',
                    ],
                )
                ->assertUnprocessable()
                ->assertJsonValidationErrors('driver_id');

            self::assertSame(
                2,
                DepotImportRow::query()
                    ->whereNull('assigned_driver_id')
                    ->count(),
            );
            self::assertDatabaseCount('depot_import_events', 1);
        } finally {
            @unlink($path);
        }
    }

    public function test_draft_creation_rejects_any_invalid_matched_route_before_writing(): void
    {
        $path = $this->workbook(
            deliveredOnFirstRoute: 101,
        );

        try {
            [$actor, $organization] = $this->context();
            $this->grantPermissions(
                $actor,
                $organization,
                ['daily-reports.enter-for-driver'],
            );
            Sanctum::actingAs($actor);

            $this->organizationRequest($organization)
                ->post(
                    self::URL,
                    $this->draftPayload($path),
                    ['Accept' => 'application/json'],
                )
                ->assertUnprocessable()
                ->assertJsonValidationErrors('workbook');

            self::assertDatabaseCount('depot_import_batches', 0);
            self::assertDatabaseCount('depot_import_rows', 0);
            self::assertDatabaseCount('daily_reports', 0);
        } finally {
            @unlink($path);
        }
    }

    private function workbook(int $deliveredOnFirstRoute = 80): string
    {
        return DepotWorkbookFactory::create(
            [
                [
                    'Rok',
                    'Měsíc',
                    'Datum',
                    'Trasa',
                    'Dopravce',
                    'Jméno řidiče',
                    'Poznámka',
                    'Čas odjezdu',
                    'Čas příjezdu',
                    'Trasa km Naměřená Position',
                    'Trasa km Plánovaná Position',
                    'Naloženo',
                    'Doručeno na adresu ks',
                    'Doručeno na VM ks',
                    'Odmítnuté',
                    'Příplatky',
                    'Nerozvezeno',
                    'Součet',
                ],
                [
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    '(důvod)',
                    null,
                    null,
                    null,
                    null,
                    'ks',
                    null,
                    null,
                    'ks',
                    null,
                    null,
                    null,
                ],
                [
                    2025,
                    6,
                    '02.06.2025',
                    35,
                    'Kökörčený',
                    'Hrůza Vít',
                    null,
                    '08:00',
                    '16:00',
                    164,
                    136,
                    100,
                    $deliveredOnFirstRoute,
                    10,
                    5,
                    0,
                    999,
                    95,
                ],
                [
                    2025,
                    6,
                    '03.06.2025',
                    36,
                    'Kökörčeny',
                    'Hrůza Vít',
                    null,
                    '08:10',
                    '15:30',
                    120,
                    115,
                    50,
                    45,
                    2,
                    1,
                    0,
                    777,
                    48,
                ],
                [
                    2025,
                    6,
                    '03.06.2025',
                    10,
                    'Jiný dopravce',
                    'Cizí řidič',
                    null,
                    '08:00',
                    '16:00',
                    50,
                    50,
                    10,
                    10,
                    0,
                    0,
                    0,
                    0,
                    10,
                ],
            ],
            [
                'R3' => [
                    'formula' => 'SUM(M3:O3)',
                    'value' => 95,
                ],
                'R4' => [
                    'formula' => 'SUM(M4:O4)',
                    'value' => 48,
                ],
            ],
        );
    }

    /** @return array<string, mixed> */
    private function draftPayload(string $path): array
    {
        return [
            'workbook' => new UploadedFile(
                $path,
                '06-2025.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true,
            ),
            'carrier_alias' => ' Kökörčený ',
            'carrier_alias_confirmed' => '1',
        ];
    }

    /** @return array{User, Organization} */
    private function context(): array
    {
        $actor = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Kökörčený',
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

    private function eligibleDriver(
        User $actor,
        Organization $organization,
        string $firstName,
        string $lastName,
        string $externalId,
        string $validFrom = '2025-01-01',
    ): Driver {
        $driverUser = User::factory()->create();
        $driver = Driver::query()->create([
            'user_id' => $driverUser->getKey(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'external_driver_id' => $externalId,
            'license_number' => 'TEST-'.$externalId,
            'active' => true,
        ]);

        DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(),
            'organization_id' => $organization->getKey(),
            'employment_type' => DriverOrganizationAssignment::EMPLOYMENT_EMPLOYEE,
            'valid_from' => $validFrom,
            'valid_until' => null,
            'created_by_user_id' => $actor->getKey(),
        ]);

        return $driver;
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
