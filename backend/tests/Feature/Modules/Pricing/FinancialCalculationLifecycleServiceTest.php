<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Models\FinancialCalculationEvent;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListVersion;
use App\Modules\Pricing\Services\FinancialCalculationLifecycleService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FinancialCalculationLifecycleServiceTest extends TestCase
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

    public function test_it_transitions_from_calculated_through_review_approval_and_closure(): void
    {
        $foundation = $this->createFoundation(true);

        $this->organizationContext()->set(
            (int) $foundation['provider']->getKey(),
        );

        $calculation = $this->createCalculation(
            $foundation,
        );

        $userId =
            (int) $foundation['user']->getKey();

        $reviewedAt = CarbonImmutable::parse(
            '2026-07-29 10:05:00',
            'Europe/Prague',
        );

        $reviewed = $this->service()->startReview(
            financialCalculationId: (int) $calculation->getKey(),
            reviewedByUserId: $userId,
            reviewedAt: $reviewedAt,
            reason: '  Manual financial review started.  ',
        );

        self::assertSame(
            FinancialCalculation::STATUS_UNDER_REVIEW,
            $reviewed->getAttribute('status'),
        );

        self::assertNull(
            $reviewed->getAttribute('approved_by_user_id'),
        );

        self::assertNull(
            $reviewed->getAttribute('approved_at'),
        );

        self::assertNull(
            $reviewed->getAttribute('closed_at'),
        );

        $approvedAt = CarbonImmutable::parse(
            '2026-07-29 10:10:00',
            'Europe/Prague',
        );

        $approved = $this->service()->approve(
            financialCalculationId: (int) $calculation->getKey(),
            approvedByUserId: $userId,
            approvedAt: $approvedAt,
            reason: '  Financial calculation approved.  ',
        );

        self::assertSame(
            FinancialCalculation::STATUS_APPROVED,
            $approved->getAttribute('status'),
        );

        self::assertSame(
            $userId,
            $approved->getAttribute('approved_by_user_id'),
        );

        $storedApprovedAt =
            $approved->getAttribute('approved_at');

        self::assertInstanceOf(
            CarbonImmutable::class,
            $storedApprovedAt,
        );

        self::assertSame(
            $approvedAt->format('Y-m-d H:i:s'),
            $storedApprovedAt->format('Y-m-d H:i:s'),
        );

        self::assertNull(
            $approved->getAttribute('closed_at'),
        );

        $closedAt = CarbonImmutable::parse(
            '2026-07-29 10:15:00',
            'Europe/Prague',
        );

        $closed = $this->service()->close(
            financialCalculationId: (int) $calculation->getKey(),
            closedByUserId: $userId,
            closedAt: $closedAt,
            reason: '  Financial calculation closed.  ',
        );

        self::assertSame(
            FinancialCalculation::STATUS_CLOSED,
            $closed->getAttribute('status'),
        );

        self::assertSame(
            $userId,
            $closed->getAttribute('approved_by_user_id'),
        );

        $storedClosedAt =
            $closed->getAttribute('closed_at');

        self::assertInstanceOf(
            CarbonImmutable::class,
            $storedClosedAt,
        );

        self::assertSame(
            $closedAt->format('Y-m-d H:i:s'),
            $storedClosedAt->format('Y-m-d H:i:s'),
        );

        self::assertCount(
            3,
            $closed->events,
        );

        $this->assertLifecycleEvent(
            calculation: $closed,
            position: 0,
            eventType: FinancialCalculationEvent::TYPE_REVIEW_STARTED,
            fromStatus: FinancialCalculation::STATUS_CALCULATED,
            toStatus: FinancialCalculation::STATUS_UNDER_REVIEW,
            actedByUserId: $userId,
            reason: 'Manual financial review started.',
            actorMetadataKey: 'reviewed_by_user_id',
        );

        $this->assertLifecycleEvent(
            calculation: $closed,
            position: 1,
            eventType: FinancialCalculationEvent::TYPE_APPROVED,
            fromStatus: FinancialCalculation::STATUS_UNDER_REVIEW,
            toStatus: FinancialCalculation::STATUS_APPROVED,
            actedByUserId: $userId,
            reason: 'Financial calculation approved.',
            actorMetadataKey: 'approved_by_user_id',
        );

        $this->assertLifecycleEvent(
            calculation: $closed,
            position: 2,
            eventType: FinancialCalculationEvent::TYPE_CLOSED,
            fromStatus: FinancialCalculation::STATUS_APPROVED,
            toStatus: FinancialCalculation::STATUS_CLOSED,
            actedByUserId: $userId,
            reason: 'Financial calculation closed.',
            actorMetadataKey: 'closed_by_user_id',
        );

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );
    }

    public function test_it_allows_cancellation_from_calculated_and_under_review_states(): void
    {
        $calculatedFoundation =
            $this->createFoundation(true);

        $this->organizationContext()->set(
            (int) $calculatedFoundation[
                'provider'
            ]->getKey(),
        );

        $calculated =
            $this->createCalculation(
                $calculatedFoundation,
            );

        $calculatedUserId =
            (int) $calculatedFoundation[
                'user'
            ]->getKey();

        $cancelledCalculated =
            $this->service()->cancel(
                financialCalculationId: (int) $calculated->getKey(),

                cancelledByUserId: $calculatedUserId,

                cancelledAt: CarbonImmutable::parse(
                    '2026-07-29 11:00:00',
                    'Europe/Prague',
                ),

                reason: '  Cancelled before review.  ',
            );

        self::assertSame(
            FinancialCalculation::STATUS_CANCELLED,
            $cancelledCalculated->getAttribute(
                'status',
            ),
        );

        self::assertCount(
            1,
            $cancelledCalculated->events,
        );

        $this->assertLifecycleEvent(
            calculation: $cancelledCalculated,
            position: 0,
            eventType: FinancialCalculationEvent::TYPE_CANCELLED,
            fromStatus: FinancialCalculation::STATUS_CALCULATED,
            toStatus: FinancialCalculation::STATUS_CANCELLED,
            actedByUserId: $calculatedUserId,
            reason: 'Cancelled before review.',
            actorMetadataKey: 'cancelled_by_user_id',
        );

        $reviewFoundation =
            $this->createFoundation(true);

        $this->organizationContext()->set(
            (int) $reviewFoundation[
                'provider'
            ]->getKey(),
        );

        $underReview =
            $this->createCalculation(
                $reviewFoundation,
            );

        $reviewUserId =
            (int) $reviewFoundation[
                'user'
            ]->getKey();

        $this->service()->startReview(
            financialCalculationId: (int) $underReview->getKey(),

            reviewedByUserId: $reviewUserId,

            reviewedAt: CarbonImmutable::parse(
                '2026-07-29 11:05:00',
                'Europe/Prague',
            ),
        );

        $cancelledReview =
            $this->service()->cancel(
                financialCalculationId: (int) $underReview->getKey(),

                cancelledByUserId: $reviewUserId,

                cancelledAt: CarbonImmutable::parse(
                    '2026-07-29 11:10:00',
                    'Europe/Prague',
                ),

                reason: '  Cancelled during review.  ',
            );

        self::assertSame(
            FinancialCalculation::STATUS_CANCELLED,
            $cancelledReview->getAttribute('status'),
        );

        self::assertCount(
            2,
            $cancelledReview->events,
        );

        $this->assertLifecycleEvent(
            calculation: $cancelledReview,
            position: 1,
            eventType: FinancialCalculationEvent::TYPE_CANCELLED,
            fromStatus: FinancialCalculation::STATUS_UNDER_REVIEW,
            toStatus: FinancialCalculation::STATUS_CANCELLED,
            actedByUserId: $reviewUserId,
            reason: 'Cancelled during review.',
            actorMetadataKey: 'cancelled_by_user_id',
        );
    }

    public function test_it_rejects_cancellation_after_approval_and_preserves_the_approved_state(): void
    {
        $foundation = $this->createFoundation(true);

        $this->organizationContext()->set(
            (int) $foundation['provider']->getKey(),
        );

        $calculation = $this->createCalculation(
            $foundation,
        );

        $userId =
            (int) $foundation['user']->getKey();

        $this->service()->startReview(
            financialCalculationId: (int) $calculation->getKey(),

            reviewedByUserId: $userId,

            reviewedAt: CarbonImmutable::parse(
                '2026-07-29 12:00:00',
                'Europe/Prague',
            ),
        );

        $this->service()->approve(
            financialCalculationId: (int) $calculation->getKey(),

            approvedByUserId: $userId,

            approvedAt: CarbonImmutable::parse(
                '2026-07-29 12:05:00',
                'Europe/Prague',
            ),
        );

        try {
            $this->service()->cancel(
                financialCalculationId: (int) $calculation->getKey(),

                cancelledByUserId: $userId,

                cancelledAt: CarbonImmutable::parse(
                    '2026-07-29 12:10:00',
                    'Europe/Prague',
                ),

                reason: 'Forbidden cancellation.',
            );

            self::fail(
                'An approved calculation was cancelled.',
            );
        } catch (DomainException $exception) {
            self::assertSame(
                (
                    'Financial calculation transition from '.
                    '"approved" to "cancelled" is not allowed.'
                ),
                $exception->getMessage(),
            );
        }

        $calculation->refresh();
        $calculation->load('events');

        self::assertSame(
            FinancialCalculation::STATUS_APPROVED,
            $calculation->getAttribute('status'),
        );

        self::assertSame(
            $userId,
            $calculation->getAttribute(
                'approved_by_user_id',
            ),
        );

        self::assertNotNull(
            $calculation->getAttribute('approved_at'),
        );

        self::assertNull(
            $calculation->getAttribute('closed_at'),
        );

        self::assertCount(
            2,
            $calculation->events,
        );

        self::assertFalse(
            $calculation->events()
                ->where(
                    'event_type',
                    FinancialCalculationEvent::TYPE_CANCELLED,
                )
                ->exists(),
        );
    }

    public function test_it_rejects_an_actor_without_compensation_permission_without_changing_the_calculation(): void
    {
        $foundation = $this->createFoundation(false);

        $this->organizationContext()->set(
            (int) $foundation['provider']->getKey(),
        );

        $calculation = $this->createCalculation(
            $foundation,
        );

        try {
            $this->service()->startReview(
                financialCalculationId: (int) $calculation->getKey(),

                reviewedByUserId: (int) $foundation['user']->getKey(),

                reviewedAt: CarbonImmutable::parse(
                    '2026-07-29 13:00:00',
                    'Europe/Prague',
                ),
            );

            self::fail(
                (
                    'A user without compensation.manage '.
                    'was accepted.'
                ),
            );
        } catch (DomainException $exception) {
            self::assertSame(
                (
                    'The acting user does not have '.
                    'the required organization permission: '.
                    'compensation.manage.'
                ),
                $exception->getMessage(),
            );
        }

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_CALCULATED,
            $calculation->getAttribute('status'),
        );

        self::assertNull(
            $calculation->getAttribute(
                'approved_by_user_id',
            ),
        );

        self::assertNull(
            $calculation->getAttribute('approved_at'),
        );

        self::assertNull(
            $calculation->getAttribute('closed_at'),
        );

        self::assertSame(
            0,
            $calculation->events()->count(),
        );

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );
    }

    /**
     * @return array{
     *     customer: Organization,
     *     provider: Organization,
     *     relationship: OrganizationRelationship,
     *     user: User,
     *     driver: Driver,
     *     dailyReport: DailyReport,
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
            'first_name' => 'Lifecycle',
            'last_name' => 'Driver',
            'phone' => null,
            'email' => null,

            'license_number' => 'LIFECYCLE-'.Str::uuid(),

            'license_category' => 'B',
            'active' => true,
        ]);

        $routeNumber =
            'LIFECYCLE-'.Str::upper(
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
            'planned_km' => '8.000',
            'actual_km' => '8.145',

            'actual_km_source' => 'delivery_application',

            'operational_notes' => 'Financial lifecycle test',

            'current_version' => 3,

            'submitted_at' => '2026-07-29 09:05:00',

            'review_started_at' => '2026-07-29 09:10:00',

            'reviewed_by_user_id' => $user->getKey(),

            'approved_at' => '2026-07-29 09:15:00',

            'approved_by_user_id' => $user->getKey(),

            'closed_at' => null,
        ]);

        $priceList = PriceList::query()->create([
            'organization_relationship_id' => $relationship->getKey(),

            'owner_organization_id' => $customer->getKey(),

            'customer_organization_id' => $customer->getKey(),

            'provider_organization_id' => $provider->getKey(),

            'name' => 'Financial lifecycle pricing '.Str::uuid(),

            'description' => 'Pricing used by lifecycle tests',

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

                'change_reason' => 'Initial lifecycle pricing',

                'created_by_user_id' => $user->getKey(),

                'approved_by_user_id' => $user->getKey(),

                'approved_at' => '2026-06-30 10:00:00',

                'activated_at' => '2026-07-01 00:00:00',
            ]);

        return [
            'customer' => $customer,
            'provider' => $provider,
            'relationship' => $relationship,
            'user' => $user,
            'driver' => $driver,
            'dailyReport' => $dailyReport,
            'priceList' => $priceList,
            'priceListVersion' => $priceListVersion,
        ];
    }

    /**
     * @param  array{
     *     customer: Organization,
     *     provider: Organization,
     *     relationship: OrganizationRelationship,
     *     user: User,
     *     driver: Driver,
     *     dailyReport: DailyReport,
     *     priceList: PriceList,
     *     priceListVersion: PriceListVersion
     * }  $foundation
     */
    private function createCalculation(
        array $foundation,
    ): FinancialCalculation {
        return FinancialCalculation::query()->create([
            'organization_id' => $foundation['provider']->getKey(),

            'organization_relationship_id' => $foundation['relationship']->getKey(),

            'price_list_id' => $foundation['priceList']->getKey(),

            'price_list_version_id' => $foundation['priceListVersion']->getKey(),

            'daily_report_id' => $foundation['dailyReport']->getKey(),

            'daily_report_version' => 3,
            'calculation_version' => 1,

            'status' => FinancialCalculation::STATUS_CALCULATED,

            'currency' => 'CZK',

            'input_snapshot' => [
                'delivered_parcels' => 20,
                'redirected_parcels' => 2,
                'undelivered_parcels' => 1,
                'actual_km' => '8.145',
            ],

            'subtotal_amount' => '194.56',
            'total_amount' => '194.56',

            'calculated_by_user_id' => $foundation['user']->getKey(),

            'calculated_at' => '2026-07-29 10:00:00',

            'approved_by_user_id' => null,
            'approved_at' => null,
            'closed_at' => null,
            'supersedes_calculation_id' => null,
        ]);
    }

    private function grantPermission(
        User $user,
        Organization $organization,
    ): void {
        $registrar = app(
            PermissionRegistrar::class,
        );

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

            $user->givePermissionTo(
                $permission,
            );
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
            'name' => 'Lifecycle organization '.Str::uuid(),

            'type' => $type,

            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function organizationContext(): OrganizationContext
    {
        return app(
            OrganizationContext::class,
        );
    }

    private function service(): FinancialCalculationLifecycleService
    {
        return app(
            FinancialCalculationLifecycleService::class,
        );
    }

    private function assertLifecycleEvent(
        FinancialCalculation $calculation,
        int $position,
        string $eventType,
        string $fromStatus,
        string $toStatus,
        int $actedByUserId,
        ?string $reason,
        string $actorMetadataKey,
    ): void {
        $event = $calculation->events
            ->values()
            ->get($position);

        self::assertInstanceOf(
            FinancialCalculationEvent::class,
            $event,
        );

        self::assertSame(
            $eventType,
            $event->getAttribute('event_type'),
        );

        self::assertSame(
            $fromStatus,
            $event->getAttribute('from_status'),
        );

        self::assertSame(
            $toStatus,
            $event->getAttribute('to_status'),
        );

        self::assertSame(
            $actedByUserId,
            $event->getAttribute(
                'acted_by_user_id',
            ),
        );

        self::assertSame(
            $reason,
            $event->getAttribute('reason'),
        );

        $metadata =
            $event->getAttribute('metadata');

        self::assertIsArray($metadata);

        self::assertSame(
            1,
            $metadata['calculation_version'],
        );

        self::assertSame(
            $actedByUserId,
            $metadata[$actorMetadataKey],
        );
    }
}
