<?php

namespace Tests\Feature\Modules\DailyReports;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Models\DailyReportEvent;
use App\Modules\DailyReports\Models\DailyReportVersion;
use App\Modules\DailyReports\Services\DailyReportPersistenceService;
use App\Modules\DailyReports\Services\DailyReportSnapshotBuilder;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class DailyReportPersistenceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizationContext()->clear();
    }

    protected function tearDown(): void
    {
        $this->organizationContext()->clear();

        parent::tearDown();
    }

    public function test_it_creates_draft_version_and_event_atomically(): void
    {
        $organization = $this->createOrganization();
        $user = $this->createActiveMember($organization);
        $driver = $this->createDriver($user);

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        $dailyReport = $this->service()->createDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $user->getKey(),
            routeNumber: '  ROUTE-101  ',
            serviceDate: '2026-07-26',
            attributes: [
                'planned_km' => '125.50',
                'operational_notes' => '  Draft prepared.  ',
            ],
            reason: '  Initial draft.  ',
        );

        self::assertSame(
            (int) $organization->getKey(),
            $dailyReport->getAttribute('organization_id'),
        );

        self::assertSame(
            DailyReport::STATUS_DRAFT,
            $dailyReport->getAttribute('status'),
        );

        self::assertSame(
            DailyReport::ENTRY_METHOD_DRIVER,
            $dailyReport->getAttribute('entry_method'),
        );

        self::assertFalse(
            $dailyReport->getAttribute('entered_on_behalf'),
        );

        self::assertSame(
            'ROUTE-101',
            $dailyReport->getAttribute('route_number'),
        );

        self::assertSame(
            'route-101',
            $dailyReport->getAttribute(
                'route_number_normalized',
            ),
        );

        self::assertSame(
            1,
            $dailyReport->getAttribute('current_version'),
        );

        self::assertTrue(
            $dailyReport->relationLoaded('versions'),
        );

        self::assertTrue(
            $dailyReport->relationLoaded('events'),
        );

        $this->assertDatabaseCount('daily_reports', 1);
        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );
        $this->assertDatabaseCount(
            'daily_report_events',
            1,
        );

        $version = DailyReportVersion::query()
            ->where(
                'daily_report_id',
                $dailyReport->getKey(),
            )
            ->sole();

        self::assertSame(
            1,
            $version->getAttribute('version_number'),
        );

        self::assertSame(
            (int) $user->getKey(),
            $version->getAttribute('created_by_user_id'),
        );

        self::assertSame(
            'Initial draft.',
            $version->getAttribute('change_reason'),
        );

        $snapshot = $version->getAttribute('snapshot');

        self::assertIsArray($snapshot);

        $actualSnapshotFields = array_keys($snapshot);
        $expectedSnapshotFields = (
            DailyReportSnapshotBuilder::SNAPSHOT_FIELDS
        );

        sort($actualSnapshotFields);
        sort($expectedSnapshotFields);

        self::assertSame(
            $expectedSnapshotFields,
            $actualSnapshotFields,
        );

        self::assertSame(
            'ROUTE-101',
            $snapshot['route_number'],
        );

        self::assertSame(
            'route-101',
            $snapshot['route_number_normalized'],
        );

        self::assertSame(
            '2026-07-26',
            $snapshot['service_date'],
        );

        self::assertSame(
            '125.50',
            $snapshot['planned_km'],
        );

        self::assertSame(
            'Draft prepared.',
            $snapshot['operational_notes'],
        );

        self::assertSame(
            DailyReportSnapshotBuilder::SNAPSHOT_FIELDS,
            $version->getAttribute('changed_fields'),
        );

        $event = DailyReportEvent::query()
            ->where(
                'daily_report_id',
                $dailyReport->getKey(),
            )
            ->sole();

        self::assertSame(
            DailyReportEvent::TYPE_CREATED,
            $event->getAttribute('event_type'),
        );

        self::assertNull(
            $event->getAttribute('from_status'),
        );

        self::assertSame(
            DailyReport::STATUS_DRAFT,
            $event->getAttribute('to_status'),
        );

        self::assertSame(
            (int) $user->getKey(),
            $event->getAttribute('acted_by_user_id'),
        );

        self::assertSame(
            'Initial draft.',
            $event->getAttribute('reason'),
        );

        self::assertSame(
            DailyReportSnapshotBuilder::SNAPSHOT_FIELDS,
            $event->getAttribute('affected_fields'),
        );

        $metadata = $event->getAttribute('metadata');

        self::assertIsArray($metadata);
        self::assertCount(2, $metadata);
        self::assertSame(1, $metadata['version_number']);
        self::assertSame(
            DailyReport::ENTRY_METHOD_DRIVER,
            $metadata['entry_method'],
        );
    }

    public function test_it_requires_verified_organization_context(): void
    {
        $organization = $this->createOrganization();
        $user = $this->createActiveMember($organization);
        $driver = $this->createDriver($user);

        try {
            $this->service()->createDraft(
                performedByDriverId: (int) $driver->getKey(),
                enteredByUserId: (int) $user->getKey(),
                routeNumber: 'ROUTE-102',
                serviceDate: '2026-07-26',
            );

            self::fail(
                'Missing organization context was accepted.',
            );
        } catch (LogicException $exception) {
            self::assertSame(
                'Verified organization context is not available.',
                $exception->getMessage(),
            );
        }

        $this->assertNoDailyReportRecords();
    }

    public function test_it_rejects_user_without_active_membership(): void
    {
        $organization = $this->createOrganization();
        $user = User::factory()->create();
        $driver = $this->createDriver($user);

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        try {
            $this->service()->createDraft(
                performedByDriverId: (int) $driver->getKey(),
                enteredByUserId: (int) $user->getKey(),
                routeNumber: 'ROUTE-103',
                serviceDate: '2026-07-26',
            );

            self::fail(
                'User without organization membership was accepted.',
            );
        } catch (DomainException $exception) {
            self::assertSame(
                (
                    'The entering user does not have an active '.
                    'membership in the verified organization.'
                ),
                $exception->getMessage(),
            );
        }

        $this->assertNoDailyReportRecords();
    }

    public function test_direct_entry_rejects_another_user_account(): void
    {
        $organization = $this->createOrganization();

        $actor = $this->createActiveMember(
            $organization,
        );

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        try {
            $this->service()->createDraft(
                performedByDriverId: (int) $driver->getKey(),
                enteredByUserId: (int) $actor->getKey(),
                routeNumber: 'ROUTE-104',
                serviceDate: '2026-07-26',
            );

            self::fail(
                'Another user account was accepted for direct entry.',
            );
        } catch (DomainException $exception) {
            self::assertSame(
                'Direct draft entry must use the driver user account.',
                $exception->getMessage(),
            );
        }

        $this->assertNoDailyReportRecords();
    }

    public function test_authorized_user_creates_delegated_draft_with_actor_identities_and_audit_events(): void
    {
        $organization = $this->createOrganization();
        $driverUser = $this->createActiveMember($organization);
        $delegatedUser = $this->createActiveMember($organization);
        $driver = $this->createDriver($driverUser);

        $this->assignOrganizationPermission(
            $delegatedUser,
            $organization,
            'daily-reports.enter-for-driver',
        );

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        $dailyReport = $this->service()->createDelegatedDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $delegatedUser->getKey(),
            routeNumber: '  ROUTE-104-D  ',
            serviceDate: '2026-07-26',
            attributes: [
                'planned_km' => '125.50',
                'operational_notes' => '  Entered for driver.  ',
            ],
            reason: '  Dispatcher entered the draft.  ',
        );

        self::assertSame(
            $driver->getKey(),
            $dailyReport->getAttribute('performed_by_driver_id'),
        );
        self::assertSame(
            $delegatedUser->getKey(),
            $dailyReport->getAttribute('entered_by_user_id'),
        );
        self::assertSame(
            DailyReport::ENTRY_METHOD_DELEGATED,
            $dailyReport->getAttribute('entry_method'),
        );
        self::assertTrue(
            $dailyReport->getAttribute('entered_on_behalf'),
        );
        self::assertSame(1, $dailyReport->getAttribute('current_version'));
        self::assertSame('ROUTE-104-D', $dailyReport->getAttribute('route_number'));
        self::assertSame(
            'Entered for driver.',
            $dailyReport->getAttribute('operational_notes'),
        );

        $this->assertDatabaseCount('daily_reports', 1);
        $this->assertDatabaseCount('daily_report_versions', 1);
        $this->assertDatabaseCount('daily_report_events', 2);

        $createdEvent = DailyReportEvent::query()
            ->where('daily_report_id', $dailyReport->getKey())
            ->where('event_type', DailyReportEvent::TYPE_CREATED)
            ->sole();

        self::assertSame(
            $delegatedUser->getKey(),
            $createdEvent->getAttribute('acted_by_user_id'),
        );

        $createdMetadata = $createdEvent->getAttribute('metadata');

        self::assertIsArray($createdMetadata);
        self::assertSame(
            DailyReport::ENTRY_METHOD_DELEGATED,
            $createdMetadata['entry_method'],
        );

        $delegatedEvent = DailyReportEvent::query()
            ->where('daily_report_id', $dailyReport->getKey())
            ->where(
                'event_type',
                DailyReportEvent::TYPE_DELEGATED_ENTRY_RECORDED,
            )
            ->sole();

        self::assertSame(
            $delegatedUser->getKey(),
            $delegatedEvent->getAttribute('acted_by_user_id'),
        );
        self::assertSame(
            DailyReport::STATUS_DRAFT,
            $delegatedEvent->getAttribute('from_status'),
        );
        self::assertSame(
            DailyReport::STATUS_DRAFT,
            $delegatedEvent->getAttribute('to_status'),
        );

        $delegatedMetadata = $delegatedEvent->getAttribute('metadata');

        self::assertIsArray($delegatedMetadata);
        self::assertSame(
            $driver->getKey(),
            $delegatedMetadata['performed_by_driver_id'],
        );
        self::assertSame(
            $delegatedUser->getKey(),
            $delegatedMetadata['entered_by_user_id'],
        );
    }

    public function test_delegated_creation_requires_enter_for_driver_permission(): void
    {
        $organization = $this->createOrganization();
        $driverUser = $this->createActiveMember($organization);
        $delegatedUser = $this->createActiveMember($organization);
        $driver = $this->createDriver($driverUser);

        Permission::findOrCreate(
            'daily-reports.enter-for-driver',
            'web',
        );

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        try {
            $this->service()->createDelegatedDraft(
                performedByDriverId: (int) $driver->getKey(),
                enteredByUserId: (int) $delegatedUser->getKey(),
                routeNumber: 'ROUTE-104-E',
                serviceDate: '2026-07-26',
            );

            self::fail(
                'Delegated draft creation without permission was accepted.',
            );
        } catch (DomainException $exception) {
            self::assertSame(
                (
                    'The acting user does not have the required '.
                    'organization permission: '.
                    'daily-reports.enter-for-driver.'
                ),
                $exception->getMessage(),
            );
        }

        $this->assertNoDailyReportRecords();
    }

    public function test_delegated_creation_event_failure_rolls_back_all_records(): void
    {
        $organization = $this->createOrganization();
        $driverUser = $this->createActiveMember($organization);
        $delegatedUser = $this->createActiveMember($organization);
        $driver = $this->createDriver($driverUser);

        $this->assignOrganizationPermission(
            $delegatedUser,
            $organization,
            'daily-reports.enter-for-driver',
        );

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        DailyReportEvent::creating(
            static function (DailyReportEvent $event): void {
                if (
                    $event->getAttribute('event_type') ===
                    DailyReportEvent::TYPE_DELEGATED_ENTRY_RECORDED
                ) {
                    throw new RuntimeException(
                        'Forced delegated entry event failure.',
                    );
                }
            },
        );

        try {
            $this->service()->createDelegatedDraft(
                performedByDriverId: (int) $driver->getKey(),
                enteredByUserId: (int) $delegatedUser->getKey(),
                routeNumber: 'ROUTE-104-F',
                serviceDate: '2026-07-26',
            );

            self::fail(
                'Forced delegated entry event failure was not propagated.',
            );
        } catch (RuntimeException $exception) {
            self::assertSame(
                'Forced delegated entry event failure.',
                $exception->getMessage(),
            );
        } finally {
            DailyReportEvent::flushEventListeners();
        }

        $this->assertNoDailyReportRecords();
    }

    public function test_original_delegated_actor_updates_and_submits_draft(): void
    {
        $organization = $this->createOrganization();
        $driverUser = $this->createActiveMember($organization);
        $delegatedUser = $this->createActiveMember($organization);
        $driver = $this->createDriver($driverUser);

        $this->assignOrganizationPermission(
            $delegatedUser,
            $organization,
            'daily-reports.enter-for-driver',
        );

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        $draft = $this->service()->createDelegatedDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $delegatedUser->getKey(),
            routeNumber: 'ROUTE-104-G',
            serviceDate: '2026-07-26',
            attributes: [
                'completion_confirmed_at' => CarbonImmutable::now(),
                'delivered_parcels' => 10,
                'redirected_parcels' => 1,
                'undelivered_parcels' => 2,
                'planned_km' => '100.00',
                'actual_km' => '102.00',
                'actual_km_source' => DailyReport::ACTUAL_KM_SOURCE_DELIVERY_APPLICATION,
            ],
        );

        $updated = $this->service()->updateDraft(
            dailyReportId: (int) $draft->getKey(),
            enteredByUserId: (int) $delegatedUser->getKey(),
            expectedVersion: 1,
            attributes: [
                'delivered_parcels' => 11,
                'operational_notes' => 'Delegated actor completed data.',
            ],
        );

        self::assertSame(2, $updated->getAttribute('current_version'));
        self::assertSame(
            DailyReport::ENTRY_METHOD_DELEGATED,
            $updated->getAttribute('entry_method'),
        );
        self::assertTrue($updated->getAttribute('entered_on_behalf'));

        $submitted = $this->service()->submitDraft(
            dailyReportId: (int) $updated->getKey(),
            enteredByUserId: (int) $delegatedUser->getKey(),
            expectedVersion: 2,
        );

        self::assertSame(
            DailyReport::STATUS_SUBMITTED,
            $submitted->getAttribute('status'),
        );
        self::assertSame(2, $submitted->getAttribute('current_version'));
        self::assertSame(
            $delegatedUser->getKey(),
            $submitted->getAttribute('entered_by_user_id'),
        );
        self::assertSame(
            $driver->getKey(),
            $submitted->getAttribute('performed_by_driver_id'),
        );
        $this->assertDatabaseCount('daily_report_versions', 2);
        $this->assertDatabaseCount('daily_report_events', 4);
    }

    public function test_database_enforces_unique_route_per_day_and_organization(): void
    {
        $organization = $this->createOrganization();
        $user = $this->createActiveMember($organization);
        $driver = $this->createDriver($user);

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        $this->service()->createDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $user->getKey(),
            routeNumber: 'Route-105',
            serviceDate: '2026-07-26',
        );

        try {
            $this->service()->createDraft(
                performedByDriverId: (int) $driver->getKey(),
                enteredByUserId: (int) $user->getKey(),
                routeNumber: '  route-105  ',
                serviceDate: '2026-07-26',
            );

            self::fail(
                'Duplicate normalized route was accepted.',
            );
        } catch (QueryException) {
        }

        $this->assertDatabaseCount('daily_reports', 1);
        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );
        $this->assertDatabaseCount(
            'daily_report_events',
            1,
        );
    }

    public function test_late_event_failure_rolls_back_report_and_version(): void
    {
        $organization = $this->createOrganization();
        $user = $this->createActiveMember($organization);
        $driver = $this->createDriver($user);

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        DailyReportEvent::creating(
            static function (): void {
                throw new RuntimeException(
                    'Forced event persistence failure.',
                );
            },
        );

        try {
            $this->service()->createDraft(
                performedByDriverId: (int) $driver->getKey(),
                enteredByUserId: (int) $user->getKey(),
                routeNumber: 'ROUTE-106',
                serviceDate: '2026-07-26',
            );

            self::fail(
                'Forced event failure was not propagated.',
            );
        } catch (RuntimeException $exception) {
            self::assertSame(
                'Forced event persistence failure.',
                $exception->getMessage(),
            );
        } finally {
            DailyReportEvent::flushEventListeners();
        }

        $this->assertNoDailyReportRecords();
    }

    public function test_it_updates_draft_with_version_snapshot_and_event_atomically(): void
    {
        $organization = $this->createOrganization();
        $user = $this->createActiveMember($organization);
        $driver = $this->createDriver($user);

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        $dailyReport = $this->service()->createDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $user->getKey(),
            routeNumber: 'ROUTE-201',
            serviceDate: '2026-07-26',
            attributes: [
                'planned_km' => '100.00',
                'operational_notes' => 'Initial notes.',
            ],
            reason: 'Initial draft.',
        );

        $updated = $this->service()->updateDraft(
            dailyReportId: (int) $dailyReport->getKey(),
            enteredByUserId: (int) $user->getKey(),
            expectedVersion: 1,
            attributes: [
                'route_number' => '  ROUTE-201-UPDATED  ',
                'service_date' => '2026-07-27',
                'delivered_parcels' => 10,
                'planned_km' => '110.25',
                'actual_km' => '112.50',
                'actual_km_source' => DailyReport::ACTUAL_KM_SOURCE_DELIVERY_APPLICATION,
                'operational_notes' => '  Updated notes.  ',
            ],
            reason: '  Driver corrected the draft.  ',
        );

        self::assertSame(
            2,
            $updated->getAttribute('current_version'),
        );

        self::assertSame(
            'ROUTE-201-UPDATED',
            $updated->getAttribute('route_number'),
        );

        self::assertSame(
            'route-201-updated',
            $updated->getAttribute(
                'route_number_normalized',
            ),
        );

        self::assertSame(
            10,
            $updated->getAttribute('delivered_parcels'),
        );

        self::assertSame(
            '110.25',
            $updated->getAttribute('planned_km'),
        );

        self::assertSame(
            '112.50',
            $updated->getAttribute('actual_km'),
        );

        self::assertSame(
            'Updated notes.',
            $updated->getAttribute('operational_notes'),
        );

        $this->assertDatabaseHas('daily_reports', [
            'id' => $dailyReport->getKey(),
            'organization_id' => $organization->getKey(),
            'route_number' => 'ROUTE-201-UPDATED',
            'route_number_normalized' => 'route-201-updated',
            'current_version' => 2,
            'status' => DailyReport::STATUS_DRAFT,
        ]);

        self::assertTrue(
            DailyReport::query()
                ->whereKey($dailyReport->getKey())
                ->whereDate(
                    'service_date',
                    '2026-07-27',
                )
                ->exists(),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            2,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            2,
        );

        $firstVersion = DailyReportVersion::query()
            ->where(
                'daily_report_id',
                $dailyReport->getKey(),
            )
            ->where('version_number', 1)
            ->sole();

        $secondVersion = DailyReportVersion::query()
            ->where(
                'daily_report_id',
                $dailyReport->getKey(),
            )
            ->where('version_number', 2)
            ->sole();

        $firstSnapshot = $firstVersion->getAttribute(
            'snapshot',
        );

        $secondSnapshot = $secondVersion->getAttribute(
            'snapshot',
        );

        self::assertIsArray($firstSnapshot);
        self::assertIsArray($secondSnapshot);

        self::assertSame(
            'Initial notes.',
            $firstSnapshot['operational_notes'],
        );

        self::assertSame(
            '2026-07-27',
            $secondSnapshot['service_date'],
        );

        self::assertSame(
            2,
            $secondSnapshot['current_version'],
        );

        $expectedChangedFields = [
            'route_number',
            'route_number_normalized',
            'service_date',
            'delivered_parcels',
            'planned_km',
            'actual_km',
            'actual_km_source',
            'operational_notes',
            'current_version',
        ];

        self::assertSame(
            $expectedChangedFields,
            $secondVersion->getAttribute('changed_fields'),
        );

        self::assertSame(
            (int) $user->getKey(),
            $secondVersion->getAttribute(
                'created_by_user_id',
            ),
        );

        self::assertSame(
            'Driver corrected the draft.',
            $secondVersion->getAttribute('change_reason'),
        );

        $updatedEvent = DailyReportEvent::query()
            ->where(
                'daily_report_id',
                $dailyReport->getKey(),
            )
            ->where(
                'event_type',
                DailyReportEvent::TYPE_UPDATED,
            )
            ->sole();

        self::assertSame(
            DailyReport::STATUS_DRAFT,
            $updatedEvent->getAttribute('from_status'),
        );

        self::assertSame(
            DailyReport::STATUS_DRAFT,
            $updatedEvent->getAttribute('to_status'),
        );

        self::assertSame(
            $expectedChangedFields,
            $updatedEvent->getAttribute('affected_fields'),
        );

        self::assertSame(
            'Driver corrected the draft.',
            $updatedEvent->getAttribute('reason'),
        );

        $metadata = $updatedEvent->getAttribute('metadata');

        self::assertIsArray($metadata);
        self::assertSame(1, $metadata['previous_version']);
        self::assertSame(2, $metadata['version_number']);
    }

    public function test_update_rejects_stale_expected_version(): void
    {
        $organization = $this->createOrganization();
        $user = $this->createActiveMember($organization);
        $driver = $this->createDriver($user);

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        $dailyReport = $this->service()->createDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $user->getKey(),
            routeNumber: 'ROUTE-202',
            serviceDate: '2026-07-26',
            attributes: [
                'planned_km' => '100.00',
            ],
        );

        $this->service()->updateDraft(
            dailyReportId: (int) $dailyReport->getKey(),
            enteredByUserId: (int) $user->getKey(),
            expectedVersion: 1,
            attributes: [
                'planned_km' => '101.00',
            ],
        );

        $rejected = false;

        try {
            $this->service()->updateDraft(
                dailyReportId: (int) $dailyReport->getKey(),
                enteredByUserId: (int) $user->getKey(),
                expectedVersion: 1,
                attributes: [
                    'operational_notes' => 'Stale update.',
                ],
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'Daily report version conflict: '.
                    'expected 1, current 2.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        $fresh = $dailyReport->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);
        self::assertSame(
            2,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('operational_notes'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            2,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            2,
        );
    }

    public function test_update_rejects_no_persisted_change(): void
    {
        $organization = $this->createOrganization();
        $user = $this->createActiveMember($organization);
        $driver = $this->createDriver($user);

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        $dailyReport = $this->service()->createDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $user->getKey(),
            routeNumber: 'ROUTE-203',
            serviceDate: '2026-07-26',
            attributes: [
                'planned_km' => '100.00',
            ],
        );

        $rejected = false;

        try {
            $this->service()->updateDraft(
                dailyReportId: (int) $dailyReport->getKey(),
                enteredByUserId: (int) $user->getKey(),
                expectedVersion: 1,
                attributes: [
                    'planned_km' => '100',
                ],
            );
        } catch (InvalidArgumentException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'Daily report draft update does not '.
                    'change any persisted field.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            1,
        );
    }

    public function test_update_rejects_non_draft_report(): void
    {
        $organization = $this->createOrganization();
        $user = $this->createActiveMember($organization);
        $driver = $this->createDriver($user);

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        $dailyReport = $this->service()->createDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $user->getKey(),
            routeNumber: 'ROUTE-204',
            serviceDate: '2026-07-26',
        );

        DailyReport::query()
            ->whereKey($dailyReport->getKey())
            ->update([
                'status' => DailyReport::STATUS_SUBMITTED,
            ]);

        $rejected = false;

        try {
            $this->service()->updateDraft(
                dailyReportId: (int) $dailyReport->getKey(),
                enteredByUserId: (int) $user->getKey(),
                expectedVersion: 1,
                attributes: [
                    'operational_notes' => 'Not allowed.',
                ],
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                'Only draft daily reports can be updated.',
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            1,
        );
    }

    public function test_update_is_scoped_to_verified_organization(): void
    {
        $firstOrganization = $this->createOrganization();
        $driverUser = $this->createActiveMember(
            $firstOrganization,
        );
        $driver = $this->createDriver($driverUser);

        $this->organizationContext()->set(
            (int) $firstOrganization->getKey(),
        );

        $dailyReport = $this->service()->createDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $driverUser->getKey(),
            routeNumber: 'ROUTE-205',
            serviceDate: '2026-07-26',
        );

        $secondOrganization = $this->createOrganization();
        $secondUser = $this->createActiveMember(
            $secondOrganization,
        );

        $this->organizationContext()->set(
            (int) $secondOrganization->getKey(),
        );

        $this->expectException(
            ModelNotFoundException::class,
        );

        try {
            $this->service()->updateDraft(
                dailyReportId: (int) $dailyReport->getKey(),
                enteredByUserId: (int) $secondUser->getKey(),
                expectedVersion: 1,
                attributes: [
                    'operational_notes' => 'Foreign update.',
                ],
            );
        } finally {
            $this->assertDatabaseCount(
                'daily_report_versions',
                1,
            );

            $this->assertDatabaseCount(
                'daily_report_events',
                1,
            );
        }
    }

    public function test_direct_update_rejects_another_user_account(): void
    {
        $organization = $this->createOrganization();
        $driverUser = $this->createActiveMember(
            $organization,
        );
        $otherUser = $this->createActiveMember(
            $organization,
        );
        $driver = $this->createDriver($driverUser);

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        $dailyReport = $this->service()->createDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $driverUser->getKey(),
            routeNumber: 'ROUTE-206',
            serviceDate: '2026-07-26',
        );

        $rejected = false;

        try {
            $this->service()->updateDraft(
                dailyReportId: (int) $dailyReport->getKey(),
                enteredByUserId: (int) $otherUser->getKey(),
                expectedVersion: 1,
                attributes: [
                    'operational_notes' => 'Wrong actor.',
                ],
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'Direct draft update must use '.
                    'the driver user account.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            1,
        );
    }

    public function test_update_event_failure_rolls_back_report_and_version(): void
    {
        $organization = $this->createOrganization();
        $user = $this->createActiveMember($organization);
        $driver = $this->createDriver($user);

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        $dailyReport = $this->service()->createDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $user->getKey(),
            routeNumber: 'ROUTE-207',
            serviceDate: '2026-07-26',
            attributes: [
                'operational_notes' => 'Original notes.',
            ],
        );

        DailyReportEvent::creating(
            static function (): void {
                throw new RuntimeException(
                    'Forced update event failure.',
                );
            },
        );

        $rejected = false;

        try {
            $this->service()->updateDraft(
                dailyReportId: (int) $dailyReport->getKey(),
                enteredByUserId: (int) $user->getKey(),
                expectedVersion: 1,
                attributes: [
                    'operational_notes' => 'Changed notes.',
                ],
            );
        } catch (RuntimeException $exception) {
            $rejected = true;

            self::assertSame(
                'Forced update event failure.',
                $exception->getMessage(),
            );
        } finally {
            DailyReportEvent::flushEventListeners();
        }

        self::assertTrue($rejected);

        $fresh = $dailyReport->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);
        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertSame(
            'Original notes.',
            $fresh->getAttribute('operational_notes'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            1,
        );
    }

    public function test_duplicate_route_update_rolls_back_atomically(): void
    {
        $organization = $this->createOrganization();
        $user = $this->createActiveMember($organization);
        $driver = $this->createDriver($user);

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        $firstReport = $this->service()->createDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $user->getKey(),
            routeNumber: 'ROUTE-208-A',
            serviceDate: '2026-07-26',
        );

        $secondReport = $this->service()->createDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $user->getKey(),
            routeNumber: 'ROUTE-208-B',
            serviceDate: '2026-07-26',
        );

        $rejected = false;

        try {
            $this->service()->updateDraft(
                dailyReportId: (int) $secondReport->getKey(),
                enteredByUserId: (int) $user->getKey(),
                expectedVersion: 1,
                attributes: [
                    'route_number' => '  route-208-a  ',
                ],
            );
        } catch (QueryException) {
            $rejected = true;
        }

        self::assertTrue($rejected);

        $freshSecondReport = $secondReport->fresh();

        self::assertInstanceOf(
            DailyReport::class,
            $freshSecondReport,
        );

        self::assertSame(
            'ROUTE-208-B',
            $freshSecondReport->getAttribute(
                'route_number',
            ),
        );

        self::assertSame(
            1,
            $freshSecondReport->getAttribute(
                'current_version',
            ),
        );

        self::assertNotSame(
            $firstReport->getKey(),
            $freshSecondReport->getKey(),
        );

        $this->assertDatabaseCount('daily_reports', 2);

        $this->assertDatabaseCount(
            'daily_report_versions',
            2,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            2,
        );
    }

    public function test_it_submits_draft_without_new_data_version_and_with_event_atomically(): void
    {
        $organization = $this->createOrganization();
        $user = $this->createActiveMember($organization);
        $driver = $this->createDriver($user);

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        $submittedAt = CarbonImmutable::now()
            ->addMinute()
            ->startOfSecond();

        CarbonImmutable::setTestNow($submittedAt);

        try {
            $dailyReport = $this->service()->createDraft(
                performedByDriverId: (int) $driver->getKey(),
                enteredByUserId: (int) $user->getKey(),
                routeNumber: 'ROUTE-301',
                serviceDate: '2026-07-26',
                attributes: [
                    'completion_confirmed_at' => CarbonImmutable::now(),
                    'delivered_parcels' => 12,
                    'redirected_parcels' => 0,
                    'undelivered_parcels' => 0,
                    'planned_km' => '100.00',
                    'actual_km' => '102.50',
                    'actual_km_source' => DailyReport::ACTUAL_KM_SOURCE_DELIVERY_APPLICATION,
                ],
            );

            $submitted = $this->service()->submitDraft(
                dailyReportId: (int) $dailyReport->getKey(),
                enteredByUserId: (int) $user->getKey(),
                expectedVersion: 1,
                reason: '  Driver completed the report.  ',
            );

            self::assertSame(
                DailyReport::STATUS_SUBMITTED,
                $submitted->getAttribute('status'),
            );

            self::assertSame(
                1,
                $submitted->getAttribute('current_version'),
            );

            $actualSubmittedAt = $submitted->getAttribute(
                'submitted_at',
            );

            self::assertInstanceOf(
                CarbonImmutable::class,
                $actualSubmittedAt,
            );

            self::assertSame(
                $submittedAt->toISOString(),
                $actualSubmittedAt->toISOString(),
            );

            $this->assertDatabaseHas('daily_reports', [
                'id' => $dailyReport->getKey(),
                'organization_id' => $organization->getKey(),
                'status' => DailyReport::STATUS_SUBMITTED,
                'current_version' => 1,
                'submitted_at' => $submittedAt->format('Y-m-d H:i:s'),
            ]);

            $this->assertDatabaseCount('daily_report_versions', 1);
            $this->assertDatabaseCount('daily_report_events', 2);

            $submittedEvent = DailyReportEvent::query()
                ->where(
                    'daily_report_id',
                    $dailyReport->getKey(),
                )
                ->where(
                    'event_type',
                    DailyReportEvent::TYPE_SUBMITTED,
                )
                ->sole();

            $expectedChangedFields = [
                'status',
                'submitted_at',
            ];

            self::assertSame(
                DailyReport::STATUS_DRAFT,
                $submittedEvent->getAttribute(
                    'from_status',
                ),
            );

            self::assertSame(
                DailyReport::STATUS_SUBMITTED,
                $submittedEvent->getAttribute(
                    'to_status',
                ),
            );

            self::assertSame(
                $expectedChangedFields,
                $submittedEvent->getAttribute(
                    'affected_fields',
                ),
            );

            self::assertSame(
                'Driver completed the report.',
                $submittedEvent->getAttribute('reason'),
            );

            $metadata = $submittedEvent->getAttribute(
                'metadata',
            );

            self::assertIsArray($metadata);
            self::assertSame(1, $metadata['version_number']);
            self::assertArrayNotHasKey('previous_version', $metadata);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_submit_rejects_missing_mandatory_operational_values(): void
    {
        $organization = $this->createOrganization();
        $user = $this->createActiveMember($organization);
        $driver = $this->createDriver($user);

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        $dailyReport = $this->service()->createDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $user->getKey(),
            routeNumber: 'ROUTE-301-INCOMPLETE',
            serviceDate: '2026-07-26',
        );

        $rejected = false;

        try {
            $this->service()->submitDraft(
                dailyReportId: (int) $dailyReport->getKey(),
                enteredByUserId: (int) $user->getKey(),
                expectedVersion: 1,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'Daily report cannot be submitted because mandatory '.
                    'operational values are missing: '.
                    'completion_confirmed_at, delivered_parcels, '.
                    'redirected_parcels, undelivered_parcels, planned_km, '.
                    'actual_km, actual_km_source.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        $fresh = $dailyReport->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);
        self::assertSame(
            DailyReport::STATUS_DRAFT,
            $fresh->getAttribute('status'),
        );
        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );
        self::assertNull(
            $fresh->getAttribute('submitted_at'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );
        $this->assertDatabaseCount(
            'daily_report_events',
            1,
        );
    }

    public function test_submit_rejects_stale_expected_version(): void
    {
        $organization = $this->createOrganization();
        $user = $this->createActiveMember($organization);
        $driver = $this->createDriver($user);

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        $dailyReport = $this->service()->createDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $user->getKey(),
            routeNumber: 'ROUTE-302',
            serviceDate: '2026-07-26',
            attributes: [
                'planned_km' => '100.00',
            ],
        );

        $this->service()->updateDraft(
            dailyReportId: (int) $dailyReport->getKey(),
            enteredByUserId: (int) $user->getKey(),
            expectedVersion: 1,
            attributes: [
                'planned_km' => '101.00',
            ],
        );

        $rejected = false;

        try {
            $this->service()->submitDraft(
                dailyReportId: (int) $dailyReport->getKey(),
                enteredByUserId: (int) $user->getKey(),
                expectedVersion: 1,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'Daily report version conflict: '.
                    'expected 1, current 2.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        $fresh = $dailyReport->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_DRAFT,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            2,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('submitted_at'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            2,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            2,
        );
    }

    public function test_submit_rejects_non_draft_report(): void
    {
        $organization = $this->createOrganization();
        $user = $this->createActiveMember($organization);
        $driver = $this->createDriver($user);

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        $dailyReport = $this->service()->createDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $user->getKey(),
            routeNumber: 'ROUTE-303',
            serviceDate: '2026-07-26',
            attributes: [
                'completion_confirmed_at' => CarbonImmutable::now(),
                'delivered_parcels' => 1,
                'redirected_parcels' => 0,
                'undelivered_parcels' => 0,
                'planned_km' => '1.00',
                'actual_km' => '1.00',
                'actual_km_source' => DailyReport::ACTUAL_KM_SOURCE_DELIVERY_APPLICATION,
            ],
        );

        $this->service()->submitDraft(
            dailyReportId: (int) $dailyReport->getKey(),
            enteredByUserId: (int) $user->getKey(),
            expectedVersion: 1,
        );

        $rejected = false;

        try {
            $this->service()->submitDraft(
                dailyReportId: (int) $dailyReport->getKey(),
                enteredByUserId: (int) $user->getKey(),
                expectedVersion: 1,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                'Only draft daily reports can be submitted.',
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            2,
        );
    }

    public function test_submit_is_scoped_to_verified_organization(): void
    {
        $firstOrganization = $this->createOrganization();
        $driverUser = $this->createActiveMember(
            $firstOrganization,
        );
        $driver = $this->createDriver($driverUser);

        $this->organizationContext()->set(
            (int) $firstOrganization->getKey(),
        );

        $dailyReport = $this->service()->createDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $driverUser->getKey(),
            routeNumber: 'ROUTE-304',
            serviceDate: '2026-07-26',
        );

        $secondOrganization = $this->createOrganization();
        $secondUser = $this->createActiveMember(
            $secondOrganization,
        );

        $this->organizationContext()->set(
            (int) $secondOrganization->getKey(),
        );

        $this->expectException(
            ModelNotFoundException::class,
        );

        try {
            $this->service()->submitDraft(
                dailyReportId: (int) $dailyReport->getKey(),
                enteredByUserId: (int) $secondUser->getKey(),
                expectedVersion: 1,
            );
        } finally {
            $this->assertDatabaseCount(
                'daily_report_versions',
                1,
            );

            $this->assertDatabaseCount(
                'daily_report_events',
                1,
            );
        }
    }

    public function test_direct_submit_rejects_another_user_account(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $otherUser = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        $dailyReport = $this->service()->createDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $driverUser->getKey(),
            routeNumber: 'ROUTE-305',
            serviceDate: '2026-07-26',
        );

        $rejected = false;

        try {
            $this->service()->submitDraft(
                dailyReportId: (int) $dailyReport->getKey(),
                enteredByUserId: (int) $otherUser->getKey(),
                expectedVersion: 1,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'Direct draft submission must use '.
                    'the driver user account.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            1,
        );
    }

    public function test_submit_rejects_inactive_membership(): void
    {
        $organization = $this->createOrganization();
        $user = $this->createActiveMember($organization);
        $driver = $this->createDriver($user);

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        $dailyReport = $this->service()->createDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $user->getKey(),
            routeNumber: 'ROUTE-306',
            serviceDate: '2026-07-26',
        );

        OrganizationMembership::query()
            ->where(
                'organization_id',
                $organization->getKey(),
            )
            ->where(
                'user_id',
                $user->getKey(),
            )
            ->update([
                'status' => OrganizationMembership::STATUS_SUSPENDED,
            ]);

        $this->expectException(DomainException::class);

        try {
            $this->service()->submitDraft(
                dailyReportId: (int) $dailyReport->getKey(),
                enteredByUserId: (int) $user->getKey(),
                expectedVersion: 1,
            );
        } finally {
            $this->assertDatabaseCount(
                'daily_report_versions',
                1,
            );

            $this->assertDatabaseCount(
                'daily_report_events',
                1,
            );
        }
    }

    public function test_submit_event_failure_rolls_back_report_without_creating_version(): void
    {
        $organization = $this->createOrganization();
        $user = $this->createActiveMember($organization);
        $driver = $this->createDriver($user);

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        $dailyReport = $this->service()->createDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $user->getKey(),
            routeNumber: 'ROUTE-307',
            serviceDate: '2026-07-26',
            attributes: [
                'completion_confirmed_at' => CarbonImmutable::now(),
                'delivered_parcels' => 1,
                'redirected_parcels' => 0,
                'undelivered_parcels' => 0,
                'planned_km' => '1.00',
                'actual_km' => '1.00',
                'actual_km_source' => DailyReport::ACTUAL_KM_SOURCE_DELIVERY_APPLICATION,
            ],
        );

        DailyReportEvent::creating(
            static function (): void {
                throw new RuntimeException(
                    'Forced submit event failure.',
                );
            },
        );

        $rejected = false;

        try {
            $this->service()->submitDraft(
                dailyReportId: (int) $dailyReport->getKey(),
                enteredByUserId: (int) $user->getKey(),
                expectedVersion: 1,
            );
        } catch (RuntimeException $exception) {
            $rejected = true;

            self::assertSame(
                'Forced submit event failure.',
                $exception->getMessage(),
            );
        } finally {
            DailyReportEvent::flushEventListeners();
        }

        self::assertTrue($rejected);

        $fresh = $dailyReport->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_DRAFT,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('submitted_at'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            1,
        );
    }

    public function test_it_starts_review_without_new_data_version_and_with_event_atomically(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $this->assignOrganizationPermission(
            $reviewer,
            $organization,
            'daily-reports.review',
        );

        $submitted = $this->createSubmittedReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            routeNumber: 'ROUTE-401',
        );

        $reviewStartedAt = CarbonImmutable::now()
            ->addMinute()
            ->startOfSecond();

        CarbonImmutable::setTestNow($reviewStartedAt);

        try {
            $underReview = $this->service()->startReview(
                dailyReportId: (int) $submitted->getKey(),
                reviewedByUserId: (int) $reviewer->getKey(),
                expectedVersion: 1,
                reason: '  Dispatcher started review.  ',
            );

            self::assertSame(
                DailyReport::STATUS_UNDER_REVIEW,
                $underReview->getAttribute('status'),
            );

            self::assertSame(
                1,
                $underReview->getAttribute('current_version'),
            );

            self::assertSame(
                $reviewer->getKey(),
                $underReview->getAttribute(
                    'reviewed_by_user_id',
                ),
            );

            $actualReviewStartedAt = $underReview->getAttribute(
                'review_started_at',
            );

            self::assertInstanceOf(
                CarbonImmutable::class,
                $actualReviewStartedAt,
            );

            self::assertSame(
                $reviewStartedAt->toISOString(),
                $actualReviewStartedAt->toISOString(),
            );

            $this->assertDatabaseHas('daily_reports', [
                'id' => $submitted->getKey(),
                'organization_id' => $organization->getKey(),
                'status' => DailyReport::STATUS_UNDER_REVIEW,
                'current_version' => 1,
                'review_started_at' => $reviewStartedAt->format('Y-m-d H:i:s'),
                'reviewed_by_user_id' => $reviewer->getKey(),
            ]);

            $this->assertDatabaseCount('daily_report_versions', 1);
            $this->assertDatabaseCount('daily_report_events', 3);

            $reviewEvent = DailyReportEvent::query()
                ->where(
                    'daily_report_id',
                    $submitted->getKey(),
                )
                ->where(
                    'event_type',
                    DailyReportEvent::TYPE_REVIEW_STARTED,
                )
                ->sole();

            $expectedChangedFields = [
                'status',
                'review_started_at',
                'reviewed_by_user_id',
            ];

            self::assertSame(
                DailyReport::STATUS_SUBMITTED,
                $reviewEvent->getAttribute('from_status'),
            );

            self::assertSame(
                DailyReport::STATUS_UNDER_REVIEW,
                $reviewEvent->getAttribute('to_status'),
            );

            self::assertSame(
                $reviewer->getKey(),
                $reviewEvent->getAttribute(
                    'acted_by_user_id',
                ),
            );

            self::assertSame(
                $expectedChangedFields,
                $reviewEvent->getAttribute(
                    'affected_fields',
                ),
            );

            self::assertSame(
                'Dispatcher started review.',
                $reviewEvent->getAttribute('reason'),
            );

            $metadata = $reviewEvent->getAttribute(
                'metadata',
            );

            self::assertIsArray($metadata);
            self::assertSame(1, $metadata['version_number']);
            self::assertArrayNotHasKey('previous_version', $metadata);

            self::assertSame(
                $reviewer->getKey(),
                $metadata['reviewed_by_user_id'],
            );

            self::assertNull(
                app(PermissionRegistrar::class)
                    ->getPermissionsTeamId(),
            );
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_start_review_requires_review_permission(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $submitted = $this->createSubmittedReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            routeNumber: 'ROUTE-402',
        );

        $rejected = false;

        try {
            $this->service()->startReview(
                dailyReportId: (int) $submitted->getKey(),
                reviewedByUserId: (int) $reviewer->getKey(),
                expectedVersion: 1,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'The acting user does not have the required '.
                    'organization permission: daily-reports.review.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        $fresh = $submitted->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_SUBMITTED,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('review_started_at'),
        );

        self::assertNull(
            $fresh->getAttribute('reviewed_by_user_id'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            2,
        );
    }

    public function test_review_permission_is_scoped_to_verified_organization(): void
    {
        $firstOrganization = $this->createOrganization();
        $reviewer = $this->createActiveMember(
            $firstOrganization,
        );

        $this->assignOrganizationPermission(
            $reviewer,
            $firstOrganization,
            'daily-reports.review',
        );

        $secondOrganization = $this->createOrganization();

        $this->createActiveMembership(
            $secondOrganization,
            $reviewer,
        );

        $driverUser = $this->createActiveMember(
            $secondOrganization,
        );

        $driver = $this->createDriver($driverUser);

        $submitted = $this->createSubmittedReport(
            organization: $secondOrganization,
            driverUser: $driverUser,
            driver: $driver,
            routeNumber: 'ROUTE-403',
        );

        $rejected = false;

        try {
            $this->service()->startReview(
                dailyReportId: (int) $submitted->getKey(),
                reviewedByUserId: (int) $reviewer->getKey(),
                expectedVersion: 1,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'The acting user does not have the required '.
                    'organization permission: daily-reports.review.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        $fresh = $submitted->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_SUBMITTED,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            2,
        );
    }

    public function test_start_review_is_scoped_to_verified_organization(): void
    {
        $firstOrganization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $firstOrganization,
        );

        $driver = $this->createDriver($driverUser);

        $submitted = $this->createSubmittedReport(
            organization: $firstOrganization,
            driverUser: $driverUser,
            driver: $driver,
            routeNumber: 'ROUTE-404',
        );

        $secondOrganization = $this->createOrganization();

        $secondReviewer = $this->createActiveMember(
            $secondOrganization,
        );

        $this->assignOrganizationPermission(
            $secondReviewer,
            $secondOrganization,
            'daily-reports.review',
        );

        $this->organizationContext()->set(
            (int) $secondOrganization->getKey(),
        );

        $this->expectException(
            ModelNotFoundException::class,
        );

        try {
            $this->service()->startReview(
                dailyReportId: (int) $submitted->getKey(),
                reviewedByUserId: (int) $secondReviewer->getKey(),
                expectedVersion: 1,
            );
        } finally {
            $fresh = $submitted->fresh();

            self::assertInstanceOf(
                DailyReport::class,
                $fresh,
            );

            self::assertSame(
                DailyReport::STATUS_SUBMITTED,
                $fresh->getAttribute('status'),
            );

            self::assertSame(
                1,
                $fresh->getAttribute('current_version'),
            );

            $this->assertDatabaseCount(
                'daily_report_versions',
                1,
            );

            $this->assertDatabaseCount(
                'daily_report_events',
                2,
            );
        }
    }

    public function test_start_review_rejects_stale_expected_version(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $this->assignOrganizationPermission(
            $reviewer,
            $organization,
            'daily-reports.review',
        );

        $submitted = $this->createSubmittedReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            routeNumber: 'ROUTE-405',
        );

        $rejected = false;

        try {
            $this->service()->startReview(
                dailyReportId: (int) $submitted->getKey(),
                reviewedByUserId: (int) $reviewer->getKey(),
                expectedVersion: 2,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'Daily report version conflict: '.
                    'expected 2, current 1.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $submitted->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_SUBMITTED,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('review_started_at'),
        );

        self::assertNull(
            $fresh->getAttribute('reviewed_by_user_id'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            2,
        );
    }

    public function test_start_review_rejects_non_submitted_report(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $this->assignOrganizationPermission(
            $reviewer,
            $organization,
            'daily-reports.review',
        );

        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        $draft = $this->service()->createDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $driverUser->getKey(),
            routeNumber: 'ROUTE-406',
            serviceDate: '2026-07-26',
        );

        $rejected = false;

        try {
            $this->service()->startReview(
                dailyReportId: (int) $draft->getKey(),
                reviewedByUserId: (int) $reviewer->getKey(),
                expectedVersion: 1,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                'Only submitted daily reports can enter review.',
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $draft->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_DRAFT,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('review_started_at'),
        );

        self::assertNull(
            $fresh->getAttribute('reviewed_by_user_id'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            1,
        );
    }

    public function test_start_review_rejects_inactive_reviewer_membership(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $this->assignOrganizationPermission(
            $reviewer,
            $organization,
            'daily-reports.review',
        );

        $submitted = $this->createSubmittedReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            routeNumber: 'ROUTE-407',
        );

        OrganizationMembership::query()
            ->where(
                'organization_id',
                $organization->getKey(),
            )
            ->where(
                'user_id',
                $reviewer->getKey(),
            )
            ->update([
                'status' => OrganizationMembership::STATUS_SUSPENDED,
            ]);

        $rejected = false;

        try {
            $this->service()->startReview(
                dailyReportId: (int) $submitted->getKey(),
                reviewedByUserId: (int) $reviewer->getKey(),
                expectedVersion: 1,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'The entering user does not have an active '.
                    'membership in the verified organization.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $submitted->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_SUBMITTED,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('review_started_at'),
        );

        self::assertNull(
            $fresh->getAttribute('reviewed_by_user_id'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            2,
        );
    }

    public function test_start_review_event_failure_rolls_back_report_without_creating_version(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $this->assignOrganizationPermission(
            $reviewer,
            $organization,
            'daily-reports.review',
        );

        $submitted = $this->createSubmittedReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            routeNumber: 'ROUTE-408',
        );

        DailyReportEvent::creating(
            static function (DailyReportEvent $event): void {
                if (
                    $event->getAttribute('event_type') ===
                    DailyReportEvent::TYPE_REVIEW_STARTED
                ) {
                    throw new RuntimeException(
                        'Forced review start event failure.',
                    );
                }
            },
        );

        $rejected = false;

        try {
            $this->service()->startReview(
                dailyReportId: (int) $submitted->getKey(),
                reviewedByUserId: (int) $reviewer->getKey(),
                expectedVersion: 1,
            );
        } catch (RuntimeException $exception) {
            $rejected = true;

            self::assertSame(
                'Forced review start event failure.',
                $exception->getMessage(),
            );
        } finally {
            DailyReportEvent::flushEventListeners();
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $submitted->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_SUBMITTED,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('review_started_at'),
        );

        self::assertNull(
            $fresh->getAttribute('reviewed_by_user_id'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            2,
        );
    }

    public function test_it_approves_report_without_new_data_version_and_with_event_atomically(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $approver = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $underReview = $this->createUnderReviewReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            routeNumber: 'ROUTE-501',
        );

        $this->assignOrganizationPermission(
            $approver,
            $organization,
            'daily-reports.approve',
        );

        $reviewStartedAt = $underReview->getAttribute(
            'review_started_at',
        );

        $submittedAt = $underReview->getAttribute(
            'submitted_at',
        );

        self::assertInstanceOf(
            CarbonImmutable::class,
            $reviewStartedAt,
        );

        self::assertInstanceOf(
            CarbonImmutable::class,
            $submittedAt,
        );

        $approvedAt = CarbonImmutable::now()
            ->addMinute()
            ->startOfSecond();

        CarbonImmutable::setTestNow($approvedAt);

        try {
            $approved = $this->service()->approve(
                dailyReportId: (int) $underReview->getKey(),
                approvedByUserId: (int) $approver->getKey(),
                expectedVersion: 1,
                reason: '  Dispatcher approved report.  ',
            );

            self::assertSame(
                DailyReport::STATUS_APPROVED,
                $approved->getAttribute('status'),
            );

            self::assertSame(
                1,
                $approved->getAttribute('current_version'),
            );

            self::assertSame(
                $approver->getKey(),
                $approved->getAttribute(
                    'approved_by_user_id',
                ),
            );

            self::assertSame(
                $reviewer->getKey(),
                $approved->getAttribute(
                    'reviewed_by_user_id',
                ),
            );

            $actualApprovedAt = $approved->getAttribute(
                'approved_at',
            );

            $actualReviewStartedAt = $approved->getAttribute(
                'review_started_at',
            );

            $actualSubmittedAt = $approved->getAttribute(
                'submitted_at',
            );

            self::assertInstanceOf(
                CarbonImmutable::class,
                $actualApprovedAt,
            );

            self::assertInstanceOf(
                CarbonImmutable::class,
                $actualReviewStartedAt,
            );

            self::assertInstanceOf(
                CarbonImmutable::class,
                $actualSubmittedAt,
            );

            self::assertSame(
                $approvedAt->toISOString(),
                $actualApprovedAt->toISOString(),
            );

            self::assertSame(
                $reviewStartedAt->toISOString(),
                $actualReviewStartedAt->toISOString(),
            );

            self::assertSame(
                $submittedAt->toISOString(),
                $actualSubmittedAt->toISOString(),
            );

            $this->assertDatabaseHas('daily_reports', [
                'id' => $underReview->getKey(),
                'organization_id' => $organization->getKey(),
                'status' => DailyReport::STATUS_APPROVED,
                'current_version' => 1,
                'approved_at' => $approvedAt->format('Y-m-d H:i:s'),
                'approved_by_user_id' => $approver->getKey(),
                'reviewed_by_user_id' => $reviewer->getKey(),
            ]);

            $this->assertDatabaseCount('daily_report_versions', 1);
            $this->assertDatabaseCount('daily_report_events', 4);

            $approvalEvent = DailyReportEvent::query()
                ->where(
                    'daily_report_id',
                    $underReview->getKey(),
                )
                ->where(
                    'event_type',
                    DailyReportEvent::TYPE_APPROVED,
                )
                ->sole();

            $expectedChangedFields = [
                'status',
                'approved_at',
                'approved_by_user_id',
            ];

            self::assertSame(
                DailyReport::STATUS_UNDER_REVIEW,
                $approvalEvent->getAttribute('from_status'),
            );

            self::assertSame(
                DailyReport::STATUS_APPROVED,
                $approvalEvent->getAttribute('to_status'),
            );

            self::assertSame(
                $approver->getKey(),
                $approvalEvent->getAttribute(
                    'acted_by_user_id',
                ),
            );

            self::assertSame(
                $expectedChangedFields,
                $approvalEvent->getAttribute(
                    'affected_fields',
                ),
            );

            self::assertSame(
                'Dispatcher approved report.',
                $approvalEvent->getAttribute('reason'),
            );

            $metadata = $approvalEvent->getAttribute(
                'metadata',
            );

            self::assertIsArray($metadata);
            self::assertSame(1, $metadata['version_number']);
            self::assertArrayNotHasKey('previous_version', $metadata);

            self::assertSame(
                $approver->getKey(),
                $metadata['approved_by_user_id'],
            );

            self::assertNull(
                app(PermissionRegistrar::class)
                    ->getPermissionsTeamId(),
            );
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_user_who_entered_report_cannot_approve_same_report(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $this->assignOrganizationPermission(
            $driverUser,
            $organization,
            'daily-reports.approve',
        );

        $underReview = $this->createUnderReviewReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            routeNumber: 'ROUTE-501-SELF-APPROVAL',
        );

        $rejected = false;

        try {
            $this->service()->approve(
                dailyReportId: (int) $underReview->getKey(),
                approvedByUserId: (int) $driverUser->getKey(),
                expectedVersion: 1,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'The user who entered a daily report cannot '.
                    'approve the same report.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        $fresh = $underReview->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_UNDER_REVIEW,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('approved_at'),
        );

        self::assertNull(
            $fresh->getAttribute('approved_by_user_id'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            3,
        );

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );
    }

    public function test_approve_requires_approve_permission(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $approver = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $underReview = $this->createUnderReviewReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            routeNumber: 'ROUTE-502',
        );

        $rejected = false;

        try {
            $this->service()->approve(
                dailyReportId: (int) $underReview->getKey(),
                approvedByUserId: (int) $approver->getKey(),
                expectedVersion: 1,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'The acting user does not have the required '.
                    'organization permission: daily-reports.approve.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        $fresh = $underReview->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_UNDER_REVIEW,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('approved_at'),
        );

        self::assertNull(
            $fresh->getAttribute('approved_by_user_id'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            3,
        );
    }

    public function test_approve_permission_is_scoped_to_verified_organization(): void
    {
        $firstOrganization = $this->createOrganization();

        $approver = $this->createActiveMember(
            $firstOrganization,
        );

        $this->assignOrganizationPermission(
            $approver,
            $firstOrganization,
            'daily-reports.approve',
        );

        $secondOrganization = $this->createOrganization();

        $this->createActiveMembership(
            $secondOrganization,
            $approver,
        );

        $driverUser = $this->createActiveMember(
            $secondOrganization,
        );

        $reviewer = $this->createActiveMember(
            $secondOrganization,
        );

        $driver = $this->createDriver($driverUser);

        $underReview = $this->createUnderReviewReport(
            organization: $secondOrganization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            routeNumber: 'ROUTE-503',
        );

        $rejected = false;

        try {
            $this->service()->approve(
                dailyReportId: (int) $underReview->getKey(),
                approvedByUserId: (int) $approver->getKey(),
                expectedVersion: 1,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'The acting user does not have the required '.
                    'organization permission: daily-reports.approve.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        $fresh = $underReview->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_UNDER_REVIEW,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('approved_at'),
        );

        self::assertNull(
            $fresh->getAttribute('approved_by_user_id'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            3,
        );
    }

    public function test_approve_is_scoped_to_verified_organization(): void
    {
        $firstOrganization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $firstOrganization,
        );

        $reviewer = $this->createActiveMember(
            $firstOrganization,
        );

        $driver = $this->createDriver($driverUser);

        $underReview = $this->createUnderReviewReport(
            organization: $firstOrganization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            routeNumber: 'ROUTE-504',
        );

        $secondOrganization = $this->createOrganization();

        $secondApprover = $this->createActiveMember(
            $secondOrganization,
        );

        $this->assignOrganizationPermission(
            $secondApprover,
            $secondOrganization,
            'daily-reports.approve',
        );

        $this->organizationContext()->set(
            (int) $secondOrganization->getKey(),
        );

        $this->expectException(
            ModelNotFoundException::class,
        );

        try {
            $this->service()->approve(
                dailyReportId: (int) $underReview->getKey(),
                approvedByUserId: (int) $secondApprover->getKey(),
                expectedVersion: 1,
            );
        } finally {
            $fresh = $underReview->fresh();

            self::assertInstanceOf(
                DailyReport::class,
                $fresh,
            );

            self::assertSame(
                DailyReport::STATUS_UNDER_REVIEW,
                $fresh->getAttribute('status'),
            );

            self::assertSame(
                1,
                $fresh->getAttribute('current_version'),
            );

            self::assertNull(
                $fresh->getAttribute('approved_at'),
            );

            self::assertNull(
                $fresh->getAttribute('approved_by_user_id'),
            );

            $this->assertDatabaseCount(
                'daily_report_versions',
                1,
            );

            $this->assertDatabaseCount(
                'daily_report_events',
                3,
            );
        }
    }

    public function test_approve_rejects_stale_expected_version(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $approver = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $underReview = $this->createUnderReviewReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            routeNumber: 'ROUTE-505',
        );

        $this->assignOrganizationPermission(
            $approver,
            $organization,
            'daily-reports.approve',
        );

        $rejected = false;

        try {
            $this->service()->approve(
                dailyReportId: (int) $underReview->getKey(),
                approvedByUserId: (int) $approver->getKey(),
                expectedVersion: 2,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'Daily report version conflict: '.
                    'expected 2, current 1.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $underReview->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_UNDER_REVIEW,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('approved_at'),
        );

        self::assertNull(
            $fresh->getAttribute('approved_by_user_id'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            3,
        );
    }

    public function test_approve_rejects_report_not_under_review(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $approver = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $this->assignOrganizationPermission(
            $approver,
            $organization,
            'daily-reports.approve',
        );

        $submitted = $this->createSubmittedReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            routeNumber: 'ROUTE-506',
        );

        $rejected = false;

        try {
            $this->service()->approve(
                dailyReportId: (int) $submitted->getKey(),
                approvedByUserId: (int) $approver->getKey(),
                expectedVersion: 1,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'Only daily reports under review '.
                    'can be approved.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $submitted->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_SUBMITTED,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('approved_at'),
        );

        self::assertNull(
            $fresh->getAttribute('approved_by_user_id'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            2,
        );
    }

    public function test_approve_rejects_inactive_approver_membership(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $approver = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $underReview = $this->createUnderReviewReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            routeNumber: 'ROUTE-507',
        );

        $this->assignOrganizationPermission(
            $approver,
            $organization,
            'daily-reports.approve',
        );

        OrganizationMembership::query()
            ->where(
                'organization_id',
                $organization->getKey(),
            )
            ->where(
                'user_id',
                $approver->getKey(),
            )
            ->update([
                'status' => OrganizationMembership::STATUS_SUSPENDED,
            ]);

        $rejected = false;

        try {
            $this->service()->approve(
                dailyReportId: (int) $underReview->getKey(),
                approvedByUserId: (int) $approver->getKey(),
                expectedVersion: 1,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'The entering user does not have an active '.
                    'membership in the verified organization.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $underReview->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_UNDER_REVIEW,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('approved_at'),
        );

        self::assertNull(
            $fresh->getAttribute('approved_by_user_id'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            3,
        );
    }

    public function test_approve_event_failure_rolls_back_report_without_creating_version(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $approver = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $underReview = $this->createUnderReviewReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            routeNumber: 'ROUTE-508',
        );

        $this->assignOrganizationPermission(
            $approver,
            $organization,
            'daily-reports.approve',
        );

        DailyReportEvent::creating(
            static function (DailyReportEvent $event): void {
                if (
                    $event->getAttribute('event_type') ===
                    DailyReportEvent::TYPE_APPROVED
                ) {
                    throw new RuntimeException(
                        'Forced approval event failure.',
                    );
                }
            },
        );

        $rejected = false;

        try {
            $this->service()->approve(
                dailyReportId: (int) $underReview->getKey(),
                approvedByUserId: (int) $approver->getKey(),
                expectedVersion: 1,
            );
        } catch (RuntimeException $exception) {
            $rejected = true;

            self::assertSame(
                'Forced approval event failure.',
                $exception->getMessage(),
            );
        } finally {
            DailyReportEvent::flushEventListeners();
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $underReview->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_UNDER_REVIEW,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('approved_at'),
        );

        self::assertNull(
            $fresh->getAttribute('approved_by_user_id'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            3,
        );
    }

    public function test_it_closes_approved_report_without_new_data_version_and_with_event_atomically(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $approver = $this->createActiveMember(
            $organization,
        );

        $closer = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $approved = $this->createApprovedReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            approver: $approver,
            routeNumber: 'ROUTE-601',
        );

        $this->assignOrganizationPermission(
            $closer,
            $organization,
            'daily-reports.close',
        );

        $approvedAt = $approved->getAttribute(
            'approved_at',
        );

        self::assertInstanceOf(
            CarbonImmutable::class,
            $approvedAt,
        );

        $closedAt = CarbonImmutable::now()
            ->addMinute()
            ->startOfSecond();

        CarbonImmutable::setTestNow($closedAt);

        try {
            $closed = $this->service()->close(
                dailyReportId: (int) $approved->getKey(),
                closedByUserId: (int) $closer->getKey(),
                expectedVersion: 1,
                reason: '  Dispatcher closed report.  ',
            );

            self::assertSame(
                DailyReport::STATUS_CLOSED,
                $closed->getAttribute('status'),
            );

            self::assertSame(
                1,
                $closed->getAttribute('current_version'),
            );

            self::assertSame(
                $approver->getKey(),
                $closed->getAttribute(
                    'approved_by_user_id',
                ),
            );

            self::assertSame(
                $reviewer->getKey(),
                $closed->getAttribute(
                    'reviewed_by_user_id',
                ),
            );

            $actualClosedAt = $closed->getAttribute(
                'closed_at',
            );

            $actualApprovedAt = $closed->getAttribute(
                'approved_at',
            );

            self::assertInstanceOf(
                CarbonImmutable::class,
                $actualClosedAt,
            );

            self::assertInstanceOf(
                CarbonImmutable::class,
                $actualApprovedAt,
            );

            self::assertSame(
                $closedAt->toISOString(),
                $actualClosedAt->toISOString(),
            );

            self::assertSame(
                $approvedAt->toISOString(),
                $actualApprovedAt->toISOString(),
            );

            $this->assertDatabaseHas('daily_reports', [
                'id' => $approved->getKey(),
                'organization_id' => $organization->getKey(),
                'status' => DailyReport::STATUS_CLOSED,
                'current_version' => 1,
                'closed_at' => $closedAt->format(
                    'Y-m-d H:i:s',
                ),
                'approved_at' => $approvedAt->format(
                    'Y-m-d H:i:s',
                ),
                'approved_by_user_id' => $approver->getKey(),
                'reviewed_by_user_id' => $reviewer->getKey(),
            ]);

            $this->assertDatabaseCount('daily_report_versions', 1);
            $this->assertDatabaseCount('daily_report_events', 5);

            $closureEvent = DailyReportEvent::query()
                ->where(
                    'daily_report_id',
                    $approved->getKey(),
                )
                ->where(
                    'event_type',
                    DailyReportEvent::TYPE_CLOSED,
                )
                ->sole();

            $expectedChangedFields = [
                'status',
                'closed_at',
            ];

            self::assertSame(
                DailyReport::STATUS_APPROVED,
                $closureEvent->getAttribute('from_status'),
            );

            self::assertSame(
                DailyReport::STATUS_CLOSED,
                $closureEvent->getAttribute('to_status'),
            );

            self::assertSame(
                $closer->getKey(),
                $closureEvent->getAttribute(
                    'acted_by_user_id',
                ),
            );

            self::assertSame(
                $expectedChangedFields,
                $closureEvent->getAttribute(
                    'affected_fields',
                ),
            );

            self::assertSame(
                'Dispatcher closed report.',
                $closureEvent->getAttribute('reason'),
            );

            $metadata = $closureEvent->getAttribute(
                'metadata',
            );

            self::assertIsArray($metadata);
            self::assertSame(1, $metadata['version_number']);
            self::assertArrayNotHasKey('previous_version', $metadata);

            self::assertSame(
                $closer->getKey(),
                $metadata['closed_by_user_id'],
            );

            self::assertNull(
                app(PermissionRegistrar::class)
                    ->getPermissionsTeamId(),
            );
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_close_requires_close_permission(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $approver = $this->createActiveMember(
            $organization,
        );

        $closer = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $approved = $this->createApprovedReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            approver: $approver,
            routeNumber: 'ROUTE-602',
        );

        $rejected = false;

        try {
            $this->service()->close(
                dailyReportId: (int) $approved->getKey(),
                closedByUserId: (int) $closer->getKey(),
                expectedVersion: 1,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'The acting user does not have the required '.
                    'organization permission: daily-reports.close.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $approved->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_APPROVED,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('closed_at'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            4,
        );
    }

    public function test_close_permission_is_organization_scoped(): void
    {
        $firstOrganization = $this->createOrganization();

        $closer = $this->createActiveMember(
            $firstOrganization,
        );

        $this->assignOrganizationPermission(
            $closer,
            $firstOrganization,
            'daily-reports.close',
        );

        $secondOrganization = $this->createOrganization();

        $this->createActiveMembership(
            $secondOrganization,
            $closer,
        );

        $driverUser = $this->createActiveMember(
            $secondOrganization,
        );

        $reviewer = $this->createActiveMember(
            $secondOrganization,
        );

        $approver = $this->createActiveMember(
            $secondOrganization,
        );

        $driver = $this->createDriver($driverUser);

        $approved = $this->createApprovedReport(
            organization: $secondOrganization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            approver: $approver,
            routeNumber: 'ROUTE-603',
        );

        $rejected = false;

        try {
            $this->service()->close(
                dailyReportId: (int) $approved->getKey(),
                closedByUserId: (int) $closer->getKey(),
                expectedVersion: 1,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'The acting user does not have the required '.
                    'organization permission: daily-reports.close.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $approved->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_APPROVED,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('closed_at'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            4,
        );
    }

    public function test_close_report_is_organization_scoped(): void
    {
        $firstOrganization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $firstOrganization,
        );

        $reviewer = $this->createActiveMember(
            $firstOrganization,
        );

        $approver = $this->createActiveMember(
            $firstOrganization,
        );

        $driver = $this->createDriver($driverUser);

        $approved = $this->createApprovedReport(
            organization: $firstOrganization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            approver: $approver,
            routeNumber: 'ROUTE-604',
        );

        $secondOrganization = $this->createOrganization();

        $secondCloser = $this->createActiveMember(
            $secondOrganization,
        );

        $this->assignOrganizationPermission(
            $secondCloser,
            $secondOrganization,
            'daily-reports.close',
        );

        $this->organizationContext()->set(
            (int) $secondOrganization->getKey(),
        );

        $this->expectException(
            ModelNotFoundException::class,
        );

        try {
            $this->service()->close(
                dailyReportId: (int) $approved->getKey(),
                closedByUserId: (int) $secondCloser->getKey(),
                expectedVersion: 1,
            );
        } finally {
            $fresh = $approved->fresh();

            self::assertInstanceOf(
                DailyReport::class,
                $fresh,
            );

            self::assertSame(
                DailyReport::STATUS_APPROVED,
                $fresh->getAttribute('status'),
            );

            self::assertSame(
                1,
                $fresh->getAttribute('current_version'),
            );

            self::assertNull(
                $fresh->getAttribute('closed_at'),
            );

            $this->assertDatabaseCount(
                'daily_report_versions',
                1,
            );

            $this->assertDatabaseCount(
                'daily_report_events',
                4,
            );
        }
    }

    public function test_close_rejects_stale_expected_version(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $approver = $this->createActiveMember(
            $organization,
        );

        $closer = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $approved = $this->createApprovedReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            approver: $approver,
            routeNumber: 'ROUTE-605',
        );

        $this->assignOrganizationPermission(
            $closer,
            $organization,
            'daily-reports.close',
        );

        $rejected = false;

        try {
            $this->service()->close(
                dailyReportId: (int) $approved->getKey(),
                closedByUserId: (int) $closer->getKey(),
                expectedVersion: 3,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'Daily report version conflict: '.
                    'expected 3, current 1.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $approved->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_APPROVED,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('closed_at'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            4,
        );
    }

    public function test_close_rejects_report_that_is_not_approved(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $closer = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $underReview = $this->createUnderReviewReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            routeNumber: 'ROUTE-606',
        );

        $this->assignOrganizationPermission(
            $closer,
            $organization,
            'daily-reports.close',
        );

        $rejected = false;

        try {
            $this->service()->close(
                dailyReportId: (int) $underReview->getKey(),
                closedByUserId: (int) $closer->getKey(),
                expectedVersion: 1,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                'Only approved daily reports can be closed.',
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $underReview->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_UNDER_REVIEW,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('closed_at'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            3,
        );
    }

    public function test_close_rejects_inactive_closer_membership(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $approver = $this->createActiveMember(
            $organization,
        );

        $closer = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $approved = $this->createApprovedReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            approver: $approver,
            routeNumber: 'ROUTE-607',
        );

        $this->assignOrganizationPermission(
            $closer,
            $organization,
            'daily-reports.close',
        );

        OrganizationMembership::query()
            ->where(
                'organization_id',
                $organization->getKey(),
            )
            ->where(
                'user_id',
                $closer->getKey(),
            )
            ->update([
                'status' => OrganizationMembership::STATUS_SUSPENDED,
            ]);

        $rejected = false;

        try {
            $this->service()->close(
                dailyReportId: (int) $approved->getKey(),
                closedByUserId: (int) $closer->getKey(),
                expectedVersion: 1,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'The entering user does not have an active '.
                    'membership in the verified organization.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $approved->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_APPROVED,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('closed_at'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            4,
        );
    }

    public function test_close_event_failure_rolls_back_report_without_creating_version(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $approver = $this->createActiveMember(
            $organization,
        );

        $closer = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $approved = $this->createApprovedReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            approver: $approver,
            routeNumber: 'ROUTE-608',
        );

        $this->assignOrganizationPermission(
            $closer,
            $organization,
            'daily-reports.close',
        );

        DailyReportEvent::creating(
            static function (DailyReportEvent $event): void {
                if (
                    $event->getAttribute('event_type') ===
                    DailyReportEvent::TYPE_CLOSED
                ) {
                    throw new RuntimeException(
                        'Forced closure event failure.',
                    );
                }
            },
        );

        $rejected = false;

        try {
            $this->service()->close(
                dailyReportId: (int) $approved->getKey(),
                closedByUserId: (int) $closer->getKey(),
                expectedVersion: 1,
            );
        } catch (RuntimeException $exception) {
            $rejected = true;

            self::assertSame(
                'Forced closure event failure.',
                $exception->getMessage(),
            );
        } finally {
            DailyReportEvent::flushEventListeners();
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $approved->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_APPROVED,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('closed_at'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            4,
        );
    }

    public function test_it_requests_correction_without_creating_new_data_version_atomically(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $requester = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $underReview = $this->createUnderReviewReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            routeNumber: 'ROUTE-701',
        );

        $this->assignOrganizationPermission(
            $requester,
            $organization,
            'daily-reports.request-correction',
        );

        $correctionRequested = $this->service()->requestCorrection(
            dailyReportId: (int) $underReview->getKey(),
            requestedByUserId: (int) $requester->getKey(),
            expectedVersion: 1,
            reason: '  Missing delivery evidence.  ',
        );

        self::assertSame(
            DailyReport::STATUS_CORRECTION_REQUESTED,
            $correctionRequested->getAttribute('status'),
        );

        self::assertSame(
            1,
            $correctionRequested->getAttribute('current_version'),
        );

        self::assertSame(
            $reviewer->getKey(),
            $correctionRequested->getAttribute(
                'reviewed_by_user_id',
            ),
        );

        $this->assertDatabaseHas('daily_reports', [
            'id' => $underReview->getKey(),
            'organization_id' => $organization->getKey(),
            'status' => DailyReport::STATUS_CORRECTION_REQUESTED,
            'current_version' => 1,
            'reviewed_by_user_id' => $reviewer->getKey(),
        ]);

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            4,
        );

        self::assertFalse(
            DailyReportVersion::query()
                ->where(
                    'daily_report_id',
                    $underReview->getKey(),
                )
                ->where('version_number', 2)
                ->exists(),
        );

        $correctionEvent = DailyReportEvent::query()
            ->where(
                'daily_report_id',
                $underReview->getKey(),
            )
            ->where(
                'event_type',
                DailyReportEvent::TYPE_CORRECTION_REQUESTED,
            )
            ->sole();

        self::assertSame(
            DailyReport::STATUS_UNDER_REVIEW,
            $correctionEvent->getAttribute('from_status'),
        );

        self::assertSame(
            DailyReport::STATUS_CORRECTION_REQUESTED,
            $correctionEvent->getAttribute('to_status'),
        );

        self::assertSame(
            $requester->getKey(),
            $correctionEvent->getAttribute(
                'acted_by_user_id',
            ),
        );

        self::assertSame(
            ['status'],
            $correctionEvent->getAttribute(
                'affected_fields',
            ),
        );

        self::assertSame(
            'Missing delivery evidence.',
            $correctionEvent->getAttribute('reason'),
        );

        $metadata = $correctionEvent->getAttribute(
            'metadata',
        );

        self::assertIsArray($metadata);
        self::assertCount(2, $metadata);

        self::assertSame(
            1,
            $metadata['version_number'],
        );

        self::assertSame(
            $requester->getKey(),
            $metadata['correction_requested_by_user_id'],
        );

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );
    }

    public function test_request_correction_requires_request_correction_permission(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $requester = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $underReview = $this->createUnderReviewReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            routeNumber: 'ROUTE-702',
        );

        $rejected = false;

        try {
            $this->service()->requestCorrection(
                dailyReportId: (int) $underReview->getKey(),
                requestedByUserId: (int) $requester->getKey(),
                expectedVersion: 1,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'The acting user does not have the required '.
                    'organization permission: daily-reports.request-correction.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $underReview->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_UNDER_REVIEW,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertSame(
            $reviewer->getKey(),
            $fresh->getAttribute('reviewed_by_user_id'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            3,
        );
    }

    public function test_request_correction_is_scoped_to_verified_organization(): void
    {
        $firstOrganization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $firstOrganization,
        );

        $reviewer = $this->createActiveMember(
            $firstOrganization,
        );

        $driver = $this->createDriver($driverUser);

        $underReview = $this->createUnderReviewReport(
            organization: $firstOrganization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            routeNumber: 'ROUTE-703',
        );

        $secondOrganization = $this->createOrganization();

        $secondRequester = $this->createActiveMember(
            $secondOrganization,
        );

        $this->assignOrganizationPermission(
            $secondRequester,
            $secondOrganization,
            'daily-reports.request-correction',
        );

        $this->organizationContext()->set(
            (int) $secondOrganization->getKey(),
        );

        $this->expectException(
            ModelNotFoundException::class,
        );

        try {
            $this->service()->requestCorrection(
                dailyReportId: (int) $underReview->getKey(),
                requestedByUserId: (int) $secondRequester->getKey(),
                expectedVersion: 1,
            );
        } finally {
            self::assertNull(
                app(PermissionRegistrar::class)
                    ->getPermissionsTeamId(),
            );

            $fresh = $underReview->fresh();

            self::assertInstanceOf(
                DailyReport::class,
                $fresh,
            );

            self::assertSame(
                DailyReport::STATUS_UNDER_REVIEW,
                $fresh->getAttribute('status'),
            );

            self::assertSame(
                1,
                $fresh->getAttribute('current_version'),
            );

            self::assertSame(
                $reviewer->getKey(),
                $fresh->getAttribute(
                    'reviewed_by_user_id',
                ),
            );

            $this->assertDatabaseCount(
                'daily_report_versions',
                1,
            );

            $this->assertDatabaseCount(
                'daily_report_events',
                3,
            );
        }
    }

    public function test_request_correction_rejects_stale_expected_version(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $requester = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $this->assignOrganizationPermission(
            $requester,
            $organization,
            'daily-reports.request-correction',
        );

        $underReview = $this->createUnderReviewReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            routeNumber: 'ROUTE-704',
        );

        $rejected = false;

        try {
            $this->service()->requestCorrection(
                dailyReportId: (int) $underReview->getKey(),
                requestedByUserId: (int) $requester->getKey(),
                expectedVersion: 2,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'Daily report version conflict: '.
                    'expected 2, current 1.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $underReview->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_UNDER_REVIEW,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertSame(
            $reviewer->getKey(),
            $fresh->getAttribute('reviewed_by_user_id'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            3,
        );
    }

    public function test_request_correction_rejects_non_under_review_report(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $requester = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $this->assignOrganizationPermission(
            $requester,
            $organization,
            'daily-reports.request-correction',
        );

        $submitted = $this->createSubmittedReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            routeNumber: 'ROUTE-705',
        );

        $rejected = false;

        try {
            $this->service()->requestCorrection(
                dailyReportId: (int) $submitted->getKey(),
                requestedByUserId: (int) $requester->getKey(),
                expectedVersion: 1,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                'Only daily reports under review can have a correction requested.',
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $submitted->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_SUBMITTED,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertNull(
            $fresh->getAttribute('review_started_at'),
        );

        self::assertNull(
            $fresh->getAttribute('reviewed_by_user_id'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            2,
        );
    }

    public function test_request_correction_rejects_inactive_requester_membership(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $requester = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $this->assignOrganizationPermission(
            $requester,
            $organization,
            'daily-reports.request-correction',
        );

        $underReview = $this->createUnderReviewReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            routeNumber: 'ROUTE-706',
        );

        OrganizationMembership::query()
            ->where(
                'organization_id',
                $organization->getKey(),
            )
            ->where(
                'user_id',
                $requester->getKey(),
            )
            ->update([
                'status' => OrganizationMembership::STATUS_SUSPENDED,
            ]);

        $rejected = false;

        try {
            $this->service()->requestCorrection(
                dailyReportId: (int) $underReview->getKey(),
                requestedByUserId: (int) $requester->getKey(),
                expectedVersion: 1,
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'The entering user does not have an active '.
                    'membership in the verified organization.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $underReview->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_UNDER_REVIEW,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertSame(
            $reviewer->getKey(),
            $fresh->getAttribute('reviewed_by_user_id'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            3,
        );
    }

    public function test_request_correction_event_failure_rolls_back_report(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $requester = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $this->assignOrganizationPermission(
            $requester,
            $organization,
            'daily-reports.request-correction',
        );

        $underReview = $this->createUnderReviewReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            routeNumber: 'ROUTE-707',
        );

        DailyReportEvent::creating(
            static function (DailyReportEvent $event): void {
                if (
                    $event->getAttribute('event_type') ===
                    DailyReportEvent::TYPE_CORRECTION_REQUESTED
                ) {
                    throw new RuntimeException(
                        'Forced correction request event failure.',
                    );
                }
            },
        );

        $rejected = false;

        try {
            $this->service()->requestCorrection(
                dailyReportId: (int) $underReview->getKey(),
                requestedByUserId: (int) $requester->getKey(),
                expectedVersion: 1,
            );
        } catch (RuntimeException $exception) {
            $rejected = true;

            self::assertSame(
                'Forced correction request event failure.',
                $exception->getMessage(),
            );
        } finally {
            DailyReportEvent::flushEventListeners();
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $underReview->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_UNDER_REVIEW,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertSame(
            $reviewer->getKey(),
            $fresh->getAttribute('reviewed_by_user_id'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            3,
        );
    }

    public function test_driver_records_correction_with_new_version_and_event_atomically(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $requester = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $correctionRequested =
            $this->createCorrectionRequestedReport(
                organization: $organization,
                driverUser: $driverUser,
                driver: $driver,
                reviewer: $reviewer,
                requester: $requester,
                routeNumber: 'ROUTE-801',
            );

        self::assertSame(
            DailyReport::STATUS_CORRECTION_REQUESTED,
            $correctionRequested->getAttribute('status'),
        );

        self::assertSame(
            1,
            $correctionRequested->getAttribute(
                'current_version',
            ),
        );

        $corrected = $this->service()->recordCorrection(
            dailyReportId: (int) $correctionRequested->getKey(),
            enteredByUserId: (int) $driverUser->getKey(),
            expectedVersion: 1,
            attributes: [
                'delivered_parcels' => 12,
                'actual_km' => '108.50',
                'operational_notes' => '  Corrected by driver.  ',
            ],
            reason: '  Driver supplied corrected values.  ',
        );

        self::assertSame(
            $correctionRequested->getKey(),
            $corrected->getKey(),
        );

        self::assertSame(
            DailyReport::STATUS_CORRECTED,
            $corrected->getAttribute('status'),
        );

        self::assertSame(
            2,
            $corrected->getAttribute('current_version'),
        );

        self::assertSame(
            $driver->getKey(),
            $corrected->getAttribute(
                'performed_by_driver_id',
            ),
        );

        self::assertSame(
            $driverUser->getKey(),
            $corrected->getAttribute(
                'entered_by_user_id',
            ),
        );

        self::assertSame(
            DailyReport::ENTRY_METHOD_DRIVER,
            $corrected->getAttribute('entry_method'),
        );

        self::assertFalse(
            $corrected->getAttribute('entered_on_behalf'),
        );

        self::assertSame(
            12,
            $corrected->getAttribute('delivered_parcels'),
        );

        self::assertSame(
            '108.50',
            $corrected->getAttribute('actual_km'),
        );

        self::assertSame(
            'Corrected by driver.',
            $corrected->getAttribute(
                'operational_notes',
            ),
        );

        self::assertSame(
            $reviewer->getKey(),
            $corrected->getAttribute(
                'reviewed_by_user_id',
            ),
        );

        $this->assertDatabaseCount('daily_reports', 1);

        $this->assertDatabaseCount(
            'daily_report_versions',
            2,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            5,
        );

        $version = DailyReportVersion::query()
            ->where(
                'daily_report_id',
                $corrected->getKey(),
            )
            ->where('version_number', 2)
            ->sole();

        self::assertSame(
            $driverUser->getKey(),
            $version->getAttribute(
                'created_by_user_id',
            ),
        );

        self::assertSame(
            'Driver supplied corrected values.',
            $version->getAttribute('change_reason'),
        );

        $changedFields = $version->getAttribute(
            'changed_fields',
        );

        self::assertIsArray($changedFields);

        self::assertContains(
            'delivered_parcels',
            $changedFields,
        );

        self::assertContains(
            'actual_km',
            $changedFields,
        );

        self::assertContains(
            'operational_notes',
            $changedFields,
        );

        $snapshot = $version->getAttribute('snapshot');

        self::assertIsArray($snapshot);

        self::assertSame(
            DailyReport::STATUS_CORRECTED,
            $snapshot['status'],
        );

        self::assertSame(
            12,
            $snapshot['delivered_parcels'],
        );

        self::assertSame(
            '108.50',
            $snapshot['actual_km'],
        );

        self::assertSame(
            'Corrected by driver.',
            $snapshot['operational_notes'],
        );

        self::assertSame(
            $driver->getKey(),
            $snapshot['performed_by_driver_id'],
        );

        self::assertSame(
            $driverUser->getKey(),
            $snapshot['entered_by_user_id'],
        );

        self::assertSame(
            DailyReport::ENTRY_METHOD_DRIVER,
            $snapshot['entry_method'],
        );

        self::assertSame(
            false,
            $snapshot['entered_on_behalf'],
        );

        $event = DailyReportEvent::query()
            ->where(
                'daily_report_id',
                $corrected->getKey(),
            )
            ->where(
                'event_type',
                DailyReportEvent::TYPE_CORRECTED,
            )
            ->sole();

        self::assertSame(
            DailyReport::STATUS_CORRECTION_REQUESTED,
            $event->getAttribute('from_status'),
        );

        self::assertSame(
            DailyReport::STATUS_CORRECTED,
            $event->getAttribute('to_status'),
        );

        self::assertSame(
            $driverUser->getKey(),
            $event->getAttribute('acted_by_user_id'),
        );

        self::assertSame(
            'Driver supplied corrected values.',
            $event->getAttribute('reason'),
        );

        $affectedFields = $event->getAttribute(
            'affected_fields',
        );

        self::assertIsArray($affectedFields);

        self::assertContains(
            'delivered_parcels',
            $affectedFields,
        );

        self::assertContains(
            'actual_km',
            $affectedFields,
        );

        self::assertContains(
            'operational_notes',
            $affectedFields,
        );

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );
    }

    public function test_corrected_report_can_be_resubmitted_and_correction_cycle_repeated_atomically(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $requester = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $correctionRequested =
            $this->createCorrectionRequestedReport(
                organization: $organization,
                driverUser: $driverUser,
                driver: $driver,
                reviewer: $reviewer,
                requester: $requester,
                routeNumber: 'ROUTE-806',
            );

        $corrected = $this->service()->recordCorrection(
            dailyReportId: (int) $correctionRequested->getKey(),
            enteredByUserId: (int) $driverUser->getKey(),
            expectedVersion: 1,
            attributes: [
                'delivered_parcels' => 12,
                'operational_notes' => 'First correction.',
            ],
            reason: 'First corrected values.',
        );

        $firstResubmittedAt = CarbonImmutable::now()
            ->addMinute()
            ->startOfSecond();

        CarbonImmutable::setTestNow($firstResubmittedAt);

        $firstResubmitted = null;

        try {
            $firstResubmitted =
                $this->service()->resubmitCorrected(
                    dailyReportId: (int) $corrected->getKey(),
                    enteredByUserId: (int) $driverUser->getKey(),
                    expectedVersion: 2,
                    reason: '  First corrected report resubmitted.  ',
                );
        } finally {
            CarbonImmutable::setTestNow();
        }

        self::assertInstanceOf(
            DailyReport::class,
            $firstResubmitted,
        );

        self::assertSame(
            DailyReport::STATUS_SUBMITTED,
            $firstResubmitted->getAttribute('status'),
        );

        self::assertSame(
            2,
            $firstResubmitted->getAttribute('current_version'),
        );

        $actualFirstResubmittedAt =
            $firstResubmitted->getAttribute('submitted_at');

        self::assertInstanceOf(
            CarbonImmutable::class,
            $actualFirstResubmittedAt,
        );

        self::assertSame(
            $firstResubmittedAt->toISOString(),
            $actualFirstResubmittedAt->toISOString(),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            2,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            6,
        );

        $firstResubmissionEvent = DailyReportEvent::query()
            ->where(
                'daily_report_id',
                $firstResubmitted->getKey(),
            )
            ->where(
                'from_status',
                DailyReport::STATUS_CORRECTED,
            )
            ->where(
                'to_status',
                DailyReport::STATUS_SUBMITTED,
            )
            ->sole();

        self::assertSame(
            DailyReportEvent::TYPE_SUBMITTED,
            $firstResubmissionEvent->getAttribute('event_type'),
        );

        self::assertSame(
            $driverUser->getKey(),
            $firstResubmissionEvent->getAttribute(
                'acted_by_user_id',
            ),
        );

        self::assertSame(
            'First corrected report resubmitted.',
            $firstResubmissionEvent->getAttribute('reason'),
        );

        $firstMetadata = $firstResubmissionEvent->getAttribute(
            'metadata',
        );

        self::assertIsArray($firstMetadata);
        self::assertSame(2, $firstMetadata['version_number']);

        $secondUnderReview = $this->service()->startReview(
            dailyReportId: (int) $firstResubmitted->getKey(),
            reviewedByUserId: (int) $reviewer->getKey(),
            expectedVersion: 2,
        );

        $secondCorrectionRequested =
            $this->service()->requestCorrection(
                dailyReportId: (int) $secondUnderReview->getKey(),
                requestedByUserId: (int) $requester->getKey(),
                expectedVersion: 2,
                reason: 'Second correction required.',
            );

        $secondCorrected = $this->service()->recordCorrection(
            dailyReportId: (int) $secondCorrectionRequested->getKey(),
            enteredByUserId: (int) $driverUser->getKey(),
            expectedVersion: 2,
            attributes: [
                'redirected_parcels' => 2,
                'operational_notes' => 'Second correction.',
            ],
            reason: 'Second corrected values.',
        );

        $secondResubmittedAt = $firstResubmittedAt
            ->addMinute();

        CarbonImmutable::setTestNow($secondResubmittedAt);

        $secondResubmitted = null;

        try {
            $secondResubmitted =
                $this->service()->resubmitCorrected(
                    dailyReportId: (int) $secondCorrected->getKey(),
                    enteredByUserId: (int) $driverUser->getKey(),
                    expectedVersion: 3,
                    reason: 'Second corrected report resubmitted.',
                );
        } finally {
            CarbonImmutable::setTestNow();
        }

        self::assertInstanceOf(
            DailyReport::class,
            $secondResubmitted,
        );

        self::assertSame(
            DailyReport::STATUS_SUBMITTED,
            $secondResubmitted->getAttribute('status'),
        );

        self::assertSame(
            3,
            $secondResubmitted->getAttribute('current_version'),
        );

        $actualSecondResubmittedAt =
            $secondResubmitted->getAttribute('submitted_at');

        self::assertInstanceOf(
            CarbonImmutable::class,
            $actualSecondResubmittedAt,
        );

        self::assertSame(
            $secondResubmittedAt->toISOString(),
            $actualSecondResubmittedAt->toISOString(),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            3,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            10,
        );

        self::assertSame(
            2,
            DailyReportEvent::query()
                ->where(
                    'daily_report_id',
                    $secondResubmitted->getKey(),
                )
                ->where(
                    'from_status',
                    DailyReport::STATUS_CORRECTED,
                )
                ->where(
                    'to_status',
                    DailyReport::STATUS_SUBMITTED,
                )
                ->count(),
        );
    }

    public function test_original_delegated_actor_with_permission_resubmits_corrected_report(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $delegatedUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $requester = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $this->assignOrganizationPermission(
            $delegatedUser,
            $organization,
            'daily-reports.enter-for-driver',
        );

        $correctionRequested =
            $this->createDelegatedCorrectionRequestedReport(
                organization: $organization,
                driverUser: $driverUser,
                driver: $driver,
                delegatedUser: $delegatedUser,
                reviewer: $reviewer,
                requester: $requester,
                routeNumber: 'ROUTE-807',
            );

        $corrected = $this->service()->recordCorrection(
            dailyReportId: (int) $correctionRequested->getKey(),
            enteredByUserId: (int) $delegatedUser->getKey(),
            expectedVersion: 1,
            attributes: [
                'delivered_parcels' => 15,
            ],
        );

        $resubmitted = $this->service()->resubmitCorrected(
            dailyReportId: (int) $corrected->getKey(),
            enteredByUserId: (int) $delegatedUser->getKey(),
            expectedVersion: 2,
            reason: 'Delegated actor resubmitted corrected report.',
        );

        self::assertSame(
            DailyReport::STATUS_SUBMITTED,
            $resubmitted->getAttribute('status'),
        );

        self::assertSame(
            2,
            $resubmitted->getAttribute('current_version'),
        );

        $event = DailyReportEvent::query()
            ->where(
                'daily_report_id',
                $resubmitted->getKey(),
            )
            ->where(
                'from_status',
                DailyReport::STATUS_CORRECTED,
            )
            ->where(
                'to_status',
                DailyReport::STATUS_SUBMITTED,
            )
            ->sole();

        self::assertSame(
            $delegatedUser->getKey(),
            $event->getAttribute('acted_by_user_id'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            2,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            6,
        );

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );
    }

    public function test_original_delegated_actor_without_permission_is_rejected_but_actual_driver_can_resubmit(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $delegatedUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $requester = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        Permission::findOrCreate(
            'daily-reports.enter-for-driver',
            'web',
        );

        $correctionRequested =
            $this->createDelegatedCorrectionRequestedReport(
                organization: $organization,
                driverUser: $driverUser,
                driver: $driver,
                delegatedUser: $delegatedUser,
                reviewer: $reviewer,
                requester: $requester,
                routeNumber: 'ROUTE-809',
            );

        $corrected = $this->service()->recordCorrection(
            dailyReportId: (int) $correctionRequested->getKey(),
            enteredByUserId: (int) $driverUser->getKey(),
            expectedVersion: 1,
            attributes: [
                'operational_notes' => 'Corrected by actual driver.',
            ],
        );

        $submittedAtBefore = $corrected->getAttribute(
            'submitted_at',
        );

        $rejected = false;

        try {
            $this->service()->resubmitCorrected(
                dailyReportId: (int) $corrected->getKey(),
                enteredByUserId: (int) $delegatedUser->getKey(),
                expectedVersion: 2,
            );
        } catch (DomainException) {
            $rejected = true;
        }

        self::assertTrue($rejected);

        $fresh = $corrected->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_CORRECTED,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            2,
            $fresh->getAttribute('current_version'),
        );

        self::assertEquals(
            $submittedAtBefore,
            $fresh->getAttribute('submitted_at'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            2,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            5,
        );

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $resubmitted = $this->service()->resubmitCorrected(
            dailyReportId: (int) $corrected->getKey(),
            enteredByUserId: (int) $driverUser->getKey(),
            expectedVersion: 2,
        );

        self::assertSame(
            DailyReport::STATUS_SUBMITTED,
            $resubmitted->getAttribute('status'),
        );

        self::assertSame(
            2,
            $resubmitted->getAttribute('current_version'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            2,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            6,
        );
    }

    public function test_resubmit_event_failure_rolls_back_corrected_report(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $requester = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $correctionRequested =
            $this->createCorrectionRequestedReport(
                organization: $organization,
                driverUser: $driverUser,
                driver: $driver,
                reviewer: $reviewer,
                requester: $requester,
                routeNumber: 'ROUTE-810',
            );

        $corrected = $this->service()->recordCorrection(
            dailyReportId: (int) $correctionRequested->getKey(),
            enteredByUserId: (int) $driverUser->getKey(),
            expectedVersion: 1,
            attributes: [
                'operational_notes' => 'Ready for resubmission.',
            ],
        );

        $submittedAtBefore = $corrected->getAttribute(
            'submitted_at',
        );

        DailyReportEvent::creating(
            static function (DailyReportEvent $event): void {
                if (
                    $event->getAttribute('event_type') ===
                    DailyReportEvent::TYPE_SUBMITTED
                ) {
                    throw new RuntimeException(
                        'Forced corrected resubmission event failure.',
                    );
                }
            },
        );

        $rejected = false;

        try {
            $this->service()->resubmitCorrected(
                dailyReportId: (int) $corrected->getKey(),
                enteredByUserId: (int) $driverUser->getKey(),
                expectedVersion: 2,
            );
        } catch (RuntimeException $exception) {
            $rejected = true;

            self::assertSame(
                'Forced corrected resubmission event failure.',
                $exception->getMessage(),
            );
        } finally {
            DailyReportEvent::flushEventListeners();
        }

        self::assertTrue($rejected);

        $fresh = $corrected->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_CORRECTED,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            2,
            $fresh->getAttribute('current_version'),
        );

        self::assertEquals(
            $submittedAtBefore,
            $fresh->getAttribute('submitted_at'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            2,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            5,
        );
    }

    public function test_original_delegated_actor_with_permission_records_correction(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $delegatedUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $requester = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $this->assignOrganizationPermission(
            $delegatedUser,
            $organization,
            'daily-reports.enter-for-driver',
        );

        $correctionRequested =
            $this->createDelegatedCorrectionRequestedReport(
                organization: $organization,
                driverUser: $driverUser,
                driver: $driver,
                delegatedUser: $delegatedUser,
                reviewer: $reviewer,
                requester: $requester,
                routeNumber: 'ROUTE-802',
            );

        self::assertSame(
            DailyReport::STATUS_CORRECTION_REQUESTED,
            $correctionRequested->getAttribute('status'),
        );

        self::assertSame(
            $driver->getKey(),
            $correctionRequested->getAttribute(
                'performed_by_driver_id',
            ),
        );

        self::assertSame(
            $delegatedUser->getKey(),
            $correctionRequested->getAttribute(
                'entered_by_user_id',
            ),
        );

        self::assertSame(
            DailyReport::ENTRY_METHOD_DELEGATED,
            $correctionRequested->getAttribute(
                'entry_method',
            ),
        );

        self::assertTrue(
            $correctionRequested->getAttribute(
                'entered_on_behalf',
            ),
        );

        $corrected = $this->service()->recordCorrection(
            dailyReportId: (int) $correctionRequested->getKey(),
            enteredByUserId: (int) $delegatedUser->getKey(),
            expectedVersion: 1,
            attributes: [
                'delivered_parcels' => 14,
                'operational_notes' => (
                    '  Corrected by delegated entry actor.  '
                ),
            ],
            reason: '  Delegated actor supplied correction.  ',
        );

        self::assertSame(
            DailyReport::STATUS_CORRECTED,
            $corrected->getAttribute('status'),
        );

        self::assertSame(
            2,
            $corrected->getAttribute('current_version'),
        );

        self::assertSame(
            $driver->getKey(),
            $corrected->getAttribute(
                'performed_by_driver_id',
            ),
        );

        self::assertSame(
            $delegatedUser->getKey(),
            $corrected->getAttribute(
                'entered_by_user_id',
            ),
        );

        self::assertSame(
            DailyReport::ENTRY_METHOD_DELEGATED,
            $corrected->getAttribute('entry_method'),
        );

        self::assertTrue(
            $corrected->getAttribute('entered_on_behalf'),
        );

        self::assertSame(
            14,
            $corrected->getAttribute('delivered_parcels'),
        );

        self::assertSame(
            'Corrected by delegated entry actor.',
            $corrected->getAttribute(
                'operational_notes',
            ),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            2,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            5,
        );

        $version = DailyReportVersion::query()
            ->where(
                'daily_report_id',
                $corrected->getKey(),
            )
            ->where('version_number', 2)
            ->sole();

        self::assertSame(
            $delegatedUser->getKey(),
            $version->getAttribute(
                'created_by_user_id',
            ),
        );

        self::assertSame(
            'Delegated actor supplied correction.',
            $version->getAttribute('change_reason'),
        );

        $snapshot = $version->getAttribute('snapshot');

        self::assertIsArray($snapshot);

        self::assertSame(
            $driver->getKey(),
            $snapshot['performed_by_driver_id'],
        );

        self::assertSame(
            $delegatedUser->getKey(),
            $snapshot['entered_by_user_id'],
        );

        self::assertSame(
            DailyReport::ENTRY_METHOD_DELEGATED,
            $snapshot['entry_method'],
        );

        self::assertSame(
            true,
            $snapshot['entered_on_behalf'],
        );

        self::assertSame(
            14,
            $snapshot['delivered_parcels'],
        );

        $event = DailyReportEvent::query()
            ->where(
                'daily_report_id',
                $corrected->getKey(),
            )
            ->where(
                'event_type',
                DailyReportEvent::TYPE_CORRECTED,
            )
            ->sole();

        self::assertSame(
            $delegatedUser->getKey(),
            $event->getAttribute('acted_by_user_id'),
        );

        self::assertSame(
            DailyReport::STATUS_CORRECTION_REQUESTED,
            $event->getAttribute('from_status'),
        );

        self::assertSame(
            DailyReport::STATUS_CORRECTED,
            $event->getAttribute('to_status'),
        );

        self::assertSame(
            'Delegated actor supplied correction.',
            $event->getAttribute('reason'),
        );

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );
    }

    public function test_original_delegated_actor_without_permission_cannot_record_correction(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $delegatedUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $requester = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        Permission::findOrCreate(
            'daily-reports.enter-for-driver',
            'web',
        );

        $correctionRequested =
            $this->createDelegatedCorrectionRequestedReport(
                organization: $organization,
                driverUser: $driverUser,
                driver: $driver,
                delegatedUser: $delegatedUser,
                reviewer: $reviewer,
                requester: $requester,
                routeNumber: 'ROUTE-803',
            );

        $rejected = false;

        try {
            $this->service()->recordCorrection(
                dailyReportId: (
                    (int) $correctionRequested->getKey()
                ),
                enteredByUserId: (
                    (int) $delegatedUser->getKey()
                ),
                expectedVersion: 1,
                attributes: [
                    'delivered_parcels' => 15,
                ],
                reason: 'Unauthorized delegated correction.',
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'The acting user does not have the required '.
                    'organization permission: '.
                    'daily-reports.enter-for-driver.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $correctionRequested->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_CORRECTION_REQUESTED,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertSame(
            $driver->getKey(),
            $fresh->getAttribute(
                'performed_by_driver_id',
            ),
        );

        self::assertSame(
            $delegatedUser->getKey(),
            $fresh->getAttribute(
                'entered_by_user_id',
            ),
        );

        self::assertSame(
            DailyReport::ENTRY_METHOD_DELEGATED,
            $fresh->getAttribute('entry_method'),
        );

        self::assertTrue(
            $fresh->getAttribute('entered_on_behalf'),
        );

        self::assertSame(
            10,
            $fresh->getAttribute('delivered_parcels'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            4,
        );

        self::assertFalse(
            DailyReportEvent::query()
                ->where(
                    'daily_report_id',
                    $fresh->getKey(),
                )
                ->where(
                    'event_type',
                    DailyReportEvent::TYPE_CORRECTED,
                )
                ->exists(),
        );
    }

    private function createCorrectionRequestedReport(
        Organization $organization,
        User $driverUser,
        Driver $driver,
        User $reviewer,
        User $requester,
        string $routeNumber,
    ): DailyReport {
        $this->assignOrganizationPermission(
            $requester,
            $organization,
            'daily-reports.request-correction',
        );

        $underReview = $this->createUnderReviewReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            routeNumber: $routeNumber,
        );

        return $this->service()->requestCorrection(
            dailyReportId: (int) $underReview->getKey(),
            requestedByUserId: (int) $requester->getKey(),
            expectedVersion: 1,
            reason: 'Correction required before approval.',
        );
    }

    public function test_other_permission_holder_cannot_correct_delegated_report(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $delegatedUser = $this->createActiveMember(
            $organization,
        );

        $otherPermissionHolder = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $requester = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        $this->assignOrganizationPermission(
            $otherPermissionHolder,
            $organization,
            'daily-reports.enter-for-driver',
        );

        $correctionRequested =
            $this->createDelegatedCorrectionRequestedReport(
                organization: $organization,
                driverUser: $driverUser,
                driver: $driver,
                delegatedUser: $delegatedUser,
                reviewer: $reviewer,
                requester: $requester,
                routeNumber: 'ROUTE-804',
            );

        $rejected = false;

        try {
            $this->service()->recordCorrection(
                dailyReportId: (
                    (int) $correctionRequested->getKey()
                ),
                enteredByUserId: (
                    (int) $otherPermissionHolder->getKey()
                ),
                expectedVersion: 1,
                attributes: [
                    'delivered_parcels' => 16,
                ],
                reason: (
                    'Other permission holder attempted correction.'
                ),
            );
        } catch (DomainException $exception) {
            $rejected = true;

            self::assertSame(
                (
                    'The correcting user is not authorized '.
                    'for this daily report.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertTrue($rejected);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $fresh = $correctionRequested->fresh();

        self::assertInstanceOf(DailyReport::class, $fresh);

        self::assertSame(
            DailyReport::STATUS_CORRECTION_REQUESTED,
            $fresh->getAttribute('status'),
        );

        self::assertSame(
            1,
            $fresh->getAttribute('current_version'),
        );

        self::assertSame(
            $driver->getKey(),
            $fresh->getAttribute(
                'performed_by_driver_id',
            ),
        );

        self::assertSame(
            $delegatedUser->getKey(),
            $fresh->getAttribute(
                'entered_by_user_id',
            ),
        );

        self::assertNotSame(
            $otherPermissionHolder->getKey(),
            $fresh->getAttribute(
                'entered_by_user_id',
            ),
        );

        self::assertSame(
            DailyReport::ENTRY_METHOD_DELEGATED,
            $fresh->getAttribute('entry_method'),
        );

        self::assertTrue(
            $fresh->getAttribute('entered_on_behalf'),
        );

        self::assertSame(
            10,
            $fresh->getAttribute('delivered_parcels'),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            1,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            4,
        );

        self::assertFalse(
            DailyReportEvent::query()
                ->where(
                    'daily_report_id',
                    $fresh->getKey(),
                )
                ->where(
                    'event_type',
                    DailyReportEvent::TYPE_CORRECTED,
                )
                ->exists(),
        );
    }

    public function test_actual_driver_can_correct_delegated_report_without_entry_permission(): void
    {
        $organization = $this->createOrganization();

        $driverUser = $this->createActiveMember(
            $organization,
        );

        $delegatedUser = $this->createActiveMember(
            $organization,
        );

        $reviewer = $this->createActiveMember(
            $organization,
        );

        $requester = $this->createActiveMember(
            $organization,
        );

        $driver = $this->createDriver($driverUser);

        self::assertFalse(
            Permission::query()
                ->where(
                    'name',
                    'daily-reports.enter-for-driver',
                )
                ->where('guard_name', 'web')
                ->exists(),
        );

        $correctionRequested =
            $this->createDelegatedCorrectionRequestedReport(
                organization: $organization,
                driverUser: $driverUser,
                driver: $driver,
                delegatedUser: $delegatedUser,
                reviewer: $reviewer,
                requester: $requester,
                routeNumber: 'ROUTE-805',
            );

        self::assertSame(
            DailyReport::STATUS_CORRECTION_REQUESTED,
            $correctionRequested->getAttribute('status'),
        );

        self::assertSame(
            $driver->getKey(),
            $correctionRequested->getAttribute(
                'performed_by_driver_id',
            ),
        );

        self::assertSame(
            $delegatedUser->getKey(),
            $correctionRequested->getAttribute(
                'entered_by_user_id',
            ),
        );

        self::assertNotSame(
            $driverUser->getKey(),
            $correctionRequested->getAttribute(
                'entered_by_user_id',
            ),
        );

        self::assertSame(
            DailyReport::ENTRY_METHOD_DELEGATED,
            $correctionRequested->getAttribute(
                'entry_method',
            ),
        );

        self::assertTrue(
            $correctionRequested->getAttribute(
                'entered_on_behalf',
            ),
        );

        $corrected = $this->service()->recordCorrection(
            dailyReportId: (
                (int) $correctionRequested->getKey()
            ),
            enteredByUserId: (int) $driverUser->getKey(),
            expectedVersion: 1,
            attributes: [
                'delivered_parcels' => 17,
                'operational_notes' => (
                    '  Corrected by the actual driver.  '
                ),
            ],
            reason: '  Actual driver supplied correction.  ',
        );

        self::assertSame(
            DailyReport::STATUS_CORRECTED,
            $corrected->getAttribute('status'),
        );

        self::assertSame(
            2,
            $corrected->getAttribute('current_version'),
        );

        self::assertSame(
            $driver->getKey(),
            $corrected->getAttribute(
                'performed_by_driver_id',
            ),
        );

        self::assertSame(
            $delegatedUser->getKey(),
            $corrected->getAttribute(
                'entered_by_user_id',
            ),
        );

        self::assertSame(
            DailyReport::ENTRY_METHOD_DELEGATED,
            $corrected->getAttribute('entry_method'),
        );

        self::assertTrue(
            $corrected->getAttribute('entered_on_behalf'),
        );

        self::assertSame(
            17,
            $corrected->getAttribute('delivered_parcels'),
        );

        self::assertSame(
            'Corrected by the actual driver.',
            $corrected->getAttribute(
                'operational_notes',
            ),
        );

        self::assertFalse(
            Permission::query()
                ->where(
                    'name',
                    'daily-reports.enter-for-driver',
                )
                ->where('guard_name', 'web')
                ->exists(),
        );

        $this->assertDatabaseCount(
            'daily_report_versions',
            2,
        );

        $this->assertDatabaseCount(
            'daily_report_events',
            5,
        );

        $version = DailyReportVersion::query()
            ->where(
                'daily_report_id',
                $corrected->getKey(),
            )
            ->where('version_number', 2)
            ->sole();

        self::assertSame(
            $driverUser->getKey(),
            $version->getAttribute(
                'created_by_user_id',
            ),
        );

        self::assertSame(
            'Actual driver supplied correction.',
            $version->getAttribute('change_reason'),
        );

        $snapshot = $version->getAttribute('snapshot');

        self::assertIsArray($snapshot);

        self::assertSame(
            $driver->getKey(),
            $snapshot['performed_by_driver_id'],
        );

        self::assertSame(
            $delegatedUser->getKey(),
            $snapshot['entered_by_user_id'],
        );

        self::assertSame(
            DailyReport::ENTRY_METHOD_DELEGATED,
            $snapshot['entry_method'],
        );

        self::assertSame(
            true,
            $snapshot['entered_on_behalf'],
        );

        self::assertSame(
            17,
            $snapshot['delivered_parcels'],
        );

        $event = DailyReportEvent::query()
            ->where(
                'daily_report_id',
                $corrected->getKey(),
            )
            ->where(
                'event_type',
                DailyReportEvent::TYPE_CORRECTED,
            )
            ->sole();

        self::assertSame(
            $driverUser->getKey(),
            $event->getAttribute('acted_by_user_id'),
        );

        self::assertSame(
            DailyReport::STATUS_CORRECTION_REQUESTED,
            $event->getAttribute('from_status'),
        );

        self::assertSame(
            DailyReport::STATUS_CORRECTED,
            $event->getAttribute('to_status'),
        );

        self::assertSame(
            'Actual driver supplied correction.',
            $event->getAttribute('reason'),
        );

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );
    }

    private function createDelegatedCorrectionRequestedReport(
        Organization $organization,
        User $driverUser,
        Driver $driver,
        User $delegatedUser,
        User $reviewer,
        User $requester,
        string $routeNumber,
    ): DailyReport {
        $correctionRequested =
            $this->createCorrectionRequestedReport(
                organization: $organization,
                driverUser: $driverUser,
                driver: $driver,
                reviewer: $reviewer,
                requester: $requester,
                routeNumber: $routeNumber,
            );

        $correctionRequested->forceFill([
            'entered_by_user_id' => $delegatedUser->getKey(),
            'entry_method' => DailyReport::ENTRY_METHOD_DELEGATED,
            'entered_on_behalf' => true,
        ])->save();

        return $correctionRequested->fresh()
            ?? throw new LogicException(
                'The delegated correction fixture could not be reloaded.',
            );
    }

    private function assignOrganizationPermission(
        User $user,
        Organization $organization,
        string $permissionName,
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
                $permissionName,
                'web',
            );

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');

            $user->givePermissionTo($permission);
        } finally {
            $registrar->setPermissionsTeamId(
                $previousOrganizationId,
            );

            $registrar->forgetCachedPermissions();
        }
    }

    private function createApprovedReport(
        Organization $organization,
        User $driverUser,
        Driver $driver,
        User $reviewer,
        User $approver,
        string $routeNumber,
    ): DailyReport {
        $this->assignOrganizationPermission(
            $approver,
            $organization,
            'daily-reports.approve',
        );

        $underReview = $this->createUnderReviewReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            reviewer: $reviewer,
            routeNumber: $routeNumber,
        );

        return $this->service()->approve(
            dailyReportId: (int) $underReview->getKey(),
            approvedByUserId: (int) $approver->getKey(),
            expectedVersion: 1,
        );
    }

    private function createUnderReviewReport(
        Organization $organization,
        User $driverUser,
        Driver $driver,
        User $reviewer,
        string $routeNumber,
    ): DailyReport {
        $this->assignOrganizationPermission(
            $reviewer,
            $organization,
            'daily-reports.review',
        );

        $submitted = $this->createSubmittedReport(
            organization: $organization,
            driverUser: $driverUser,
            driver: $driver,
            routeNumber: $routeNumber,
        );

        return $this->service()->startReview(
            dailyReportId: (int) $submitted->getKey(),
            reviewedByUserId: (int) $reviewer->getKey(),
            expectedVersion: 1,
        );
    }

    private function createSubmittedReport(
        Organization $organization,
        User $driverUser,
        Driver $driver,
        string $routeNumber,
    ): DailyReport {
        $this->organizationContext()->set(
            (int) $organization->getKey(),
        );

        $draft = $this->service()->createDraft(
            performedByDriverId: (int) $driver->getKey(),
            enteredByUserId: (int) $driverUser->getKey(),
            routeNumber: $routeNumber,
            serviceDate: '2026-07-26',
            attributes: [
                'completion_confirmed_at' => CarbonImmutable::now(),
                'delivered_parcels' => 10,
                'redirected_parcels' => 1,
                'undelivered_parcels' => 2,
                'planned_km' => '100.00',
                'actual_km' => '103.00',
                'actual_km_source' => DailyReport::ACTUAL_KM_SOURCE_DELIVERY_APPLICATION,
            ],
        );

        return $this->service()->submitDraft(
            dailyReportId: (int) $draft->getKey(),
            enteredByUserId: (int) $driverUser->getKey(),
            expectedVersion: 1,
        );
    }

    private function createActiveMembership(
        Organization $organization,
        User $user,
    ): void {
        OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subMinute(),
            'valid_until' => null,
        ]);
    }

    private function organizationContext(): OrganizationContext
    {
        return app(OrganizationContext::class);
    }

    private function service(): DailyReportPersistenceService
    {
        return app(DailyReportPersistenceService::class);
    }

    private function createOrganization(): Organization
    {
        return Organization::query()->create([
            'name' => 'R11 organization '.Str::uuid(),
            'type' => Organization::TYPE_CARRIER,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function createActiveMember(
        Organization $organization,
    ): User {
        $user = User::factory()->create();

        OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subMinute(),
            'valid_until' => null,
        ]);

        return $user;
    }

    private function createDriver(User $user): Driver
    {
        return Driver::query()->create([
            'user_id' => $user->getKey(),
            'first_name' => 'R11',
            'last_name' => 'Driver',
            'phone' => null,
            'email' => null,
            'license_number' => 'R11-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);
    }

    private function assertNoDailyReportRecords(): void
    {
        $this->assertDatabaseCount('daily_reports', 0);
        $this->assertDatabaseCount(
            'daily_report_versions',
            0,
        );
        $this->assertDatabaseCount(
            'daily_report_events',
            0,
        );
    }
}
