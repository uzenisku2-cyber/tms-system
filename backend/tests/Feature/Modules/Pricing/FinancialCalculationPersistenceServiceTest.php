<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Models\DailyReportVersion;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Models\FinancialCalculationEvent;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListItem;
use App\Modules\Pricing\Models\PriceListVersion;
use App\Modules\Pricing\Services\FinancialCalculationPersistenceService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FinancialCalculationPersistenceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizationContext()->clear();

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId(null);
        $registrar->forgetCachedPermissions();
    }

    protected function tearDown(): void
    {
        $this->organizationContext()->clear();

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId(null);
        $registrar->forgetCachedPermissions();

        parent::tearDown();
    }

    public function test_it_persists_initial_calculation_lines_and_event_atomically(): void
    {
        $foundation = $this->createFoundation(true);

        $this->organizationContext()->set(
            (int) $foundation['provider']->getKey(),
        );

        $calculation = $this->service()
            ->createInitialCalculation(
                dailyReportVersionId: (int) $foundation[
                        'dailyReportVersion'
                    ]->getKey(),

                priceListVersionId: (int) $foundation[
                        'priceListVersion'
                    ]->getKey(),

                calculatedByUserId: (int) $foundation['user']->getKey(),

                calculatedAt: CarbonImmutable::parse(
                    '2026-07-29 21:30:00',
                    'Europe/Prague',
                ),

                reason: '  Initial approved calculation.  ',
            );

        $calculation->refresh();
        $calculation->load(['lines', 'events']);

        self::assertSame(
            $foundation['provider']->getKey(),
            $calculation->getAttribute('organization_id'),
        );

        self::assertSame(
            $foundation['dailyReport']->getKey(),
            $calculation->getAttribute('daily_report_id'),
        );

        self::assertSame(
            4,
            $calculation->getAttribute(
                'daily_report_version',
            ),
        );

        self::assertSame(
            FinancialCalculation::STATUS_CALCULATED,
            $calculation->getAttribute('status'),
        );

        self::assertSame(
            'CZK',
            $calculation->getAttribute('currency'),
        );

        self::assertSame(
            '194.56',
            $calculation->getAttribute(
                'subtotal_amount',
            ),
        );

        self::assertSame(
            '194.56',
            $calculation->getAttribute('total_amount'),
        );

        $inputSnapshot =
            $calculation->getAttribute('input_snapshot');

        self::assertIsArray($inputSnapshot);

        self::assertSame(
            4,
            $inputSnapshot['daily_report_version'],
        );

        self::assertSame(
            20,
            $inputSnapshot['delivered_parcels'],
        );

        self::assertSame(
            '8.145',
            $inputSnapshot['actual_km'],
        );

        self::assertSame(
            '2026-07-29 21:30:00',
            $inputSnapshot['captured_at'],
        );

        self::assertCount(4, $calculation->lines);
        self::assertCount(1, $calculation->events);

        $expectedLines = [
            [
                'delivered_parcels',
                '20.000',
                '4.2500',
                '85.00',
                1,
            ],
            [
                'redirected_parcels',
                '2.000',
                '1.0050',
                '2.01',
                2,
            ],
            [
                'undelivered_parcels',
                '1.000',
                '7.0000',
                '7.00',
                3,
            ],
            [
                'actual_km',
                '8.145',
                '12.3456',
                '100.55',
                4,
            ],
        ];

        foreach (
            $expectedLines as [
                $code,
                $quantity,
                $rate,
                $amount,
                $position,
            ]
        ) {
            $this->assertDatabaseHas(
                'financial_calculation_lines',
                [
                    'financial_calculation_id' => $calculation->getKey(),

                    'pricing_code' => $code,
                    'quantity' => $quantity,
                    'unit_rate' => $rate,
                    'line_amount' => $amount,
                    'position' => $position,
                ],
            );
        }

        $event = $calculation->events()->sole();

        self::assertSame(
            FinancialCalculationEvent::TYPE_CALCULATED,
            $event->getAttribute('event_type'),
        );

        self::assertNull(
            $event->getAttribute('from_status'),
        );

        self::assertSame(
            FinancialCalculation::STATUS_CALCULATED,
            $event->getAttribute('to_status'),
        );

        self::assertSame(
            'Initial approved calculation.',
            $event->getAttribute('reason'),
        );

        $metadata = $event->getAttribute('metadata');

        self::assertIsArray($metadata);
        self::assertSame(4, $metadata['line_count']);

        self::assertSame(
            '194.56',
            $metadata['total_amount'],
        );

        $this->assertDatabaseCount(
            'financial_calculations',
            1,
        );

        $this->assertDatabaseCount(
            'financial_calculation_lines',
            4,
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            1,
        );

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );
    }

    public function test_it_rejects_a_user_without_compensation_permission(): void
    {
        $foundation = $this->createFoundation(false);

        $this->organizationContext()->set(
            (int) $foundation['provider']->getKey(),
        );

        try {
            $this->service()->createInitialCalculation(
                dailyReportVersionId: (int) $foundation[
                        'dailyReportVersion'
                    ]->getKey(),

                priceListVersionId: (int) $foundation[
                        'priceListVersion'
                    ]->getKey(),

                calculatedByUserId: (int) $foundation['user']->getKey(),

                calculatedAt: CarbonImmutable::parse(
                    '2026-07-29 21:35:00',
                    'Europe/Prague',
                ),
            );

            self::fail(
                'A user without compensation.manage was accepted.',
            );
        } catch (DomainException $exception) {
            self::assertSame(
                (
                    'The calculating user does not have '.
                    'the required organization permission: '.
                    'compensation.manage.'
                ),
                $exception->getMessage(),
            );
        }

        $this->assertNoFinancialRecords();

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );
    }

    public function test_it_rejects_a_duplicate_initial_calculation(): void
    {
        $foundation = $this->createFoundation(true);

        $this->organizationContext()->set(
            (int) $foundation['provider']->getKey(),
        );

        $dailyReportVersionId =
            (int) $foundation[
                'dailyReportVersion'
            ]->getKey();

        $priceListVersionId =
            (int) $foundation[
                'priceListVersion'
            ]->getKey();

        $userId =
            (int) $foundation['user']->getKey();

        $this->service()->createInitialCalculation(
            dailyReportVersionId: $dailyReportVersionId,

            priceListVersionId: $priceListVersionId,

            calculatedByUserId: $userId,

            calculatedAt: CarbonImmutable::parse(
                '2026-07-29 21:40:00',
                'Europe/Prague',
            ),
        );

        try {
            $this->service()->createInitialCalculation(
                dailyReportVersionId: $dailyReportVersionId,

                priceListVersionId: $priceListVersionId,

                calculatedByUserId: $userId,

                calculatedAt: CarbonImmutable::parse(
                    '2026-07-29 21:41:00',
                    'Europe/Prague',
                ),
            );

            self::fail(
                'A duplicate calculation was accepted.',
            );
        } catch (DomainException $exception) {
            self::assertSame(
                (
                    'The daily-report version has already '.
                    'been calculated.'
                ),
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseCount(
            'financial_calculations',
            1,
        );

        $this->assertDatabaseCount(
            'financial_calculation_lines',
            4,
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            1,
        );
    }

    public function test_it_rejects_a_stale_daily_report_version(): void
    {
        $foundation = $this->createFoundation(true);

        $foundation['dailyReport']->setAttribute(
            'current_version',
            5,
        );

        $foundation['dailyReport']->saveOrFail();

        $this->organizationContext()->set(
            (int) $foundation['provider']->getKey(),
        );

        try {
            $this->service()->createInitialCalculation(
                dailyReportVersionId: (int) $foundation[
                        'dailyReportVersion'
                    ]->getKey(),

                priceListVersionId: (int) $foundation[
                        'priceListVersion'
                    ]->getKey(),

                calculatedByUserId: (int) $foundation['user']->getKey(),

                calculatedAt: CarbonImmutable::parse(
                    '2026-07-29 21:42:00',
                    'Europe/Prague',
                ),
            );

            self::fail(
                'A stale daily-report version was accepted.',
            );
        } catch (DomainException $exception) {
            self::assertSame(
                (
                    'Only the current daily-report version can create '.
                    'an initial financial calculation; selected version 4, '.
                    'current version 5.'
                ),
                $exception->getMessage(),
            );
        }

        $this->assertNoFinancialRecords();
    }

    public function test_it_rejects_a_relationship_not_valid_on_the_service_date(): void
    {
        $foundation = $this->createFoundation(true);

        $foundation['relationship']->setAttribute(
            'valid_from',
            '2026-07-30 00:00:00',
        );

        $foundation['relationship']->saveOrFail();

        $this->organizationContext()->set(
            (int) $foundation['provider']->getKey(),
        );

        try {
            $this->service()->createInitialCalculation(
                dailyReportVersionId: (int) $foundation[
                        'dailyReportVersion'
                    ]->getKey(),

                priceListVersionId: (int) $foundation[
                        'priceListVersion'
                    ]->getKey(),

                calculatedByUserId: (int) $foundation['user']->getKey(),

                calculatedAt: CarbonImmutable::parse(
                    '2026-07-29 21:43:00',
                    'Europe/Prague',
                ),
            );

            self::fail(
                'A relationship outside the service date was accepted.',
            );
        } catch (DomainException $exception) {
            self::assertSame(
                (
                    'The selected commercial relationship is not '.
                    'applicable on daily-report service date [2026-07-29].'
                ),
                $exception->getMessage(),
            );
        }

        $this->assertNoFinancialRecords();
    }

    public function test_it_rejects_a_price_list_version_not_valid_on_the_service_date(): void
    {
        $foundation = $this->createFoundation(true);

        $foundation['priceListVersion']->setAttribute(
            'valid_from',
            '2026-07-30',
        );

        $foundation['priceListVersion']->saveOrFail();

        $this->organizationContext()->set(
            (int) $foundation['provider']->getKey(),
        );

        try {
            $this->service()->createInitialCalculation(
                dailyReportVersionId: (int) $foundation[
                        'dailyReportVersion'
                    ]->getKey(),

                priceListVersionId: (int) $foundation[
                        'priceListVersion'
                    ]->getKey(),

                calculatedByUserId: (int) $foundation['user']->getKey(),

                calculatedAt: CarbonImmutable::parse(
                    '2026-07-29 21:44:00',
                    'Europe/Prague',
                ),
            );

            self::fail(
                'A price-list version outside the service date was accepted.',
            );
        } catch (DomainException $exception) {
            self::assertSame(
                (
                    'The selected price-list version is not applicable on '.
                    'daily-report service date [2026-07-29].'
                ),
                $exception->getMessage(),
            );
        }

        $this->assertNoFinancialRecords();
    }

    public function test_event_failure_rolls_back_calculation_and_lines(): void
    {
        $foundation = $this->createFoundation(true);

        $this->organizationContext()->set(
            (int) $foundation['provider']->getKey(),
        );

        FinancialCalculationEvent::creating(
            static function (): void {
                throw new RuntimeException(
                    'Forced financial event failure.',
                );
            },
        );

        try {
            $this->service()->createInitialCalculation(
                dailyReportVersionId: (int) $foundation[
                        'dailyReportVersion'
                    ]->getKey(),

                priceListVersionId: (int) $foundation[
                        'priceListVersion'
                    ]->getKey(),

                calculatedByUserId: (int) $foundation['user']->getKey(),

                calculatedAt: CarbonImmutable::parse(
                    '2026-07-29 21:45:00',
                    'Europe/Prague',
                ),
            );

            self::fail(
                'The forced event failure was ignored.',
            );
        } catch (RuntimeException $exception) {
            self::assertSame(
                'Forced financial event failure.',
                $exception->getMessage(),
            );
        } finally {
            FinancialCalculationEvent::flushEventListeners();
        }

        $this->assertNoFinancialRecords();
    }

    /**
     * @return array{
     *     customer: Organization,
     *     provider: Organization,
     *     relationship: OrganizationRelationship,
     *     user: User,
     *     dailyReport: DailyReport,
     *     dailyReportVersion: DailyReportVersion,
     *     priceList: PriceList,
     *     priceListVersion: PriceListVersion
     * }
     */
    private function createFoundation(
        bool $grantPermission,
    ): array {
        $customer = $this->createOrganization(
            Organization::TYPE_CARRIER,
        );

        $provider = $this->createOrganization(
            Organization::TYPE_SUBCONTRACTOR,
        );

        $relationship =
            OrganizationRelationship::query()->create([
                'source_organization_id' => $customer->getKey(),

                'target_organization_id' => $provider->getKey(),

                'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,

                'status' => OrganizationRelationship::STATUS_ACTIVE,

                'valid_from' => now()->subMonth(),
                'valid_until' => null,
            ]);

        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
        ]);

        OrganizationMembership::query()->create([
            'organization_id' => $provider->getKey(),

            'user_id' => $user->getKey(),

            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,

            'status' => OrganizationMembership::STATUS_ACTIVE,

            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);

        if ($grantPermission) {
            $this->grantPermission(
                $user,
                $provider,
            );
        }

        $driver = Driver::query()->create([
            'user_id' => $user->getKey(),
            'first_name' => 'Financial',
            'last_name' => 'Driver',
            'phone' => null,
            'email' => null,

            'license_number' => 'FINANCIAL-'.Str::uuid(),

            'license_category' => 'B',
            'active' => true,
        ]);

        $routeNumber =
            'FINANCIAL-'.Str::upper(
                Str::random(12),
            );

        $dailyReport = DailyReport::query()->create([
            'organization_id' => $customer->getKey(),
            'trip_id' => null,

            'performed_by_driver_id' => $driver->getKey(),

            'vehicle_id' => null,
            'entered_by_user_id' => $user->getKey(),
            'route_number' => $routeNumber,

            'route_number_normalized' => Str::lower($routeNumber),

            'service_date' => '2026-07-29',
            'status' => DailyReport::STATUS_APPROVED,

            'entry_method' => DailyReport::ENTRY_METHOD_DRIVER,

            'entered_on_behalf' => false,

            'completion_confirmed_at' => '2026-07-29 09:00:00',

            'delivered_parcels' => 20,
            'redirected_parcels' => 2,
            'undelivered_parcels' => 1,
            'planned_km' => '9.000',
            'actual_km' => '8.145',

            'actual_km_source' => 'delivery_application',

            'operational_notes' => 'Financial persistence test',

            'current_version' => 4,

            'submitted_at' => '2026-07-29 09:05:00',

            'review_started_at' => '2026-07-29 09:10:00',

            'reviewed_by_user_id' => $user->getKey(),

            'approved_at' => '2026-07-29 09:15:00',

            'approved_by_user_id' => $user->getKey(),

            'closed_at' => null,
        ]);

        $snapshot = [
            'public_id' => (string)
                $dailyReport->getAttribute('public_id'),

            'organization_id' => $customer->getKey(),
            'trip_id' => null,

            'performed_by_driver_id' => $driver->getKey(),

            'vehicle_id' => null,
            'route_number' => $routeNumber,

            'route_number_normalized' => Str::lower($routeNumber),

            'service_date' => '2026-07-29',
            'status' => DailyReport::STATUS_APPROVED,
            'delivered_parcels' => 20,
            'redirected_parcels' => 2,
            'undelivered_parcels' => 1,
            'planned_km' => '9.000',
            'actual_km' => '8.145',

            'actual_km_source' => 'delivery_application',

            'current_version' => 4,

            'approved_at' => '2026-07-29 09:15:00',

            'approved_by_user_id' => $user->getKey(),

            'closed_at' => null,
        ];

        $dailyReportVersion =
            DailyReportVersion::query()->create([
                'daily_report_id' => $dailyReport->getKey(),

                'version_number' => 4,
                'snapshot' => $snapshot,
                'changed_fields' => [],

                'created_by_user_id' => $user->getKey(),

                'change_reason' => 'Approved financial snapshot',

                'created_at' => '2026-07-29 09:15:00',
            ]);

        $priceList = PriceList::query()->create([
            'organization_relationship_id' => $relationship->getKey(),

            'owner_organization_id' => $customer->getKey(),

            'customer_organization_id' => $customer->getKey(),

            'provider_organization_id' => $provider->getKey(),

            'name' => 'Financial persistence pricing',

            'description' => 'Persistence service test pricing',

            'currency' => 'CZK',
            'status' => PriceList::STATUS_ACTIVE,
            'current_version' => 1,

            'created_by_user_id' => $user->getKey(),
        ]);

        $priceListVersion =
            $priceList->versions()->create([
                'version_number' => 1,

                'status' => PriceListVersion::STATUS_ACTIVE,

                'valid_from' => '2026-07-01',
                'valid_until' => null,

                'change_reason' => 'Initial active pricing',

                'created_by_user_id' => $user->getKey(),

                'approved_by_user_id' => $user->getKey(),

                'approved_at' => '2026-06-30 10:00:00',

                'activated_at' => '2026-07-01 00:00:00',
            ]);

        $items = [
            [
                PriceListItem::CODE_DELIVERED_PARCELS,

                'Delivered parcels',
                PriceListItem::UNIT_PARCEL,
                '4.2500',

                PriceListItem::QUANTITY_SOURCE_DELIVERED_PARCELS,

                1,
            ],
            [
                PriceListItem::CODE_REDIRECTED_PARCELS,

                'Redirected parcels',
                PriceListItem::UNIT_PARCEL,
                '1.0050',

                PriceListItem::QUANTITY_SOURCE_REDIRECTED_PARCELS,

                2,
            ],
            [
                PriceListItem::CODE_UNDELIVERED_PARCELS,

                'Undelivered parcels',
                PriceListItem::UNIT_PARCEL,
                '7.0000',

                PriceListItem::QUANTITY_SOURCE_UNDELIVERED_PARCELS,

                3,
            ],
            [
                PriceListItem::CODE_ACTUAL_KM,
                'Actual kilometres',
                PriceListItem::UNIT_KM,
                '12.3456',

                PriceListItem::QUANTITY_SOURCE_ACTUAL_KM,

                4,
            ],
        ];

        foreach (
            $items as [
                $code,
                $description,
                $unit,
                $rate,
                $source,
                $position,
            ]
        ) {
            $priceListVersion->items()->create([
                'code' => $code,
                'description' => $description,
                'unit' => $unit,
                'unit_rate' => $rate,
                'currency' => 'CZK',
                'quantity_source' => $source,
                'position' => $position,
            ]);
        }

        return [
            'customer' => $customer,
            'provider' => $provider,
            'relationship' => $relationship,
            'user' => $user,
            'dailyReport' => $dailyReport,
            'dailyReportVersion' => $dailyReportVersion,

            'priceList' => $priceList,
            'priceListVersion' => $priceListVersion,
        ];
    }

    private function grantPermission(
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
                'compensation.manage',
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

    private function createOrganization(
        string $type,
    ): Organization {
        return Organization::query()->create([
            'name' => 'Financial organization '.Str::uuid(),

            'type' => $type,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function organizationContext(): OrganizationContext
    {
        return app(OrganizationContext::class);
    }

    private function service(): FinancialCalculationPersistenceService
    {
        return app(
            FinancialCalculationPersistenceService::class,
        );
    }

    private function assertNoFinancialRecords(): void
    {
        $this->assertDatabaseCount(
            'financial_calculations',
            0,
        );

        $this->assertDatabaseCount(
            'financial_calculation_lines',
            0,
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            0,
        );
    }
}
