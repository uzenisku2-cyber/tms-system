<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Models\FinancialCalculationEvent;
use App\Modules\Pricing\Models\FinancialCalculationLine;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListItem;
use App\Modules\Pricing\Models\PriceListVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FinancialCalculationReadApiTest extends TestCase
{
    use RefreshDatabase;

    private const INDEX_URL =
        '/api/v1/financial-calculations';

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_guest_cannot_access_financial_calculations(): void
    {
        $publicId = (string) Str::uuid();

        $this->getJson(self::INDEX_URL)
            ->assertUnauthorized();

        $this->getJson(
            self::INDEX_URL.'/'.$publicId,
        )->assertUnauthorized();

        $this->getJson(
            self::INDEX_URL.'/'.$publicId.'/events',
        )->assertUnauthorized();
    }

    public function test_missing_organization_context_is_rejected(): void
    {
        $user = User::factory()->create();

        $organization = $this->createOrganization(
            Organization::TYPE_CARRIER,
        );

        $this->addMembership(
            $user,
            $organization,
        );

        $this->grantViewPermission(
            $user,
            $organization,
        );

        Sanctum::actingAs($user);

        $this->getJson(self::INDEX_URL)
            ->assertStatus(400);
    }

    public function test_compensation_view_permission_is_required(): void
    {
        $user = User::factory()->create();

        $organization = $this->createOrganization(
            Organization::TYPE_CARRIER,
        );

        $this->addMembership(
            $user,
            $organization,
        );

        Sanctum::actingAs($user);

        $this->withOrganization($organization)
            ->getJson(self::INDEX_URL)
            ->assertForbidden();
    }

    public function test_index_is_visible_to_both_relationship_parties_and_scoped(): void
    {
        $user = User::factory()->create();

        $customer = $this->createOrganization(
            Organization::TYPE_CARRIER,
        );

        $provider = $this->createOrganization(
            Organization::TYPE_SUBCONTRACTOR,
        );

        $historicalRelationship =
            $this->createRelationship(
                customer: $customer,
                provider: $provider,
                validUntil: now()->subDay(),
            );

        $this->addMembership(
            $user,
            $customer,
        );

        $this->addMembership(
            $user,
            $provider,
        );

        $this->grantViewPermission(
            $user,
            $customer,
        );

        $this->grantViewPermission(
            $user,
            $provider,
        );

        $older = $this->createCalculationFixture(
            customer: $customer,
            provider: $provider,
            relationship: $historicalRelationship,
            actor: $user,
            overrides: [
                'calculated_at' => '2026-07-29 10:00:00',
                'subtotal_amount' => '100.00',
                'total_amount' => '100.00',
            ],
        )['calculation'];

        $newer = $this->createCalculationFixture(
            customer: $customer,
            provider: $provider,
            relationship: $historicalRelationship,
            actor: $user,
            overrides: [
                'calculated_at' => '2026-07-30 10:00:00',
                'subtotal_amount' => '200.00',
                'total_amount' => '200.00',
            ],
        )['calculation'];

        $unrelatedCustomer = $this->createOrganization(
            Organization::TYPE_CARRIER,
        );

        $unrelatedProvider = $this->createOrganization(
            Organization::TYPE_SUBCONTRACTOR,
        );

        $unrelatedRelationship =
            $this->createRelationship(
                $unrelatedCustomer,
                $unrelatedProvider,
            );

        $unrelated = $this->createCalculationFixture(
            customer: $unrelatedCustomer,
            provider: $unrelatedProvider,
            relationship: $unrelatedRelationship,
            actor: $user,
            overrides: [
                'calculated_at' => '2026-07-31 10:00:00',
                'subtotal_amount' => '300.00',
                'total_amount' => '300.00',
            ],
        )['calculation'];

        Sanctum::actingAs($user);

        $customerResponse =
            $this->withOrganization($customer)
                ->getJson(
                    self::INDEX_URL.
                    '?sort_by=calculated_at'.
                    '&sort_dir=desc'.
                    '&per_page=1',
                );

        $customerResponse
            ->assertOk()
            ->assertJsonPath(
                'data.items.0.public_id',
                (string) $newer->getAttribute('public_id'),
            )
            ->assertJsonPath(
                'data.pagination.current_page',
                1,
            )
            ->assertJsonPath(
                'data.pagination.last_page',
                2,
            )
            ->assertJsonPath(
                'data.pagination.per_page',
                1,
            )
            ->assertJsonPath(
                'data.pagination.total',
                2,
            )
            ->assertJsonMissing([
                'public_id' => (string) $unrelated->getAttribute(
                    'public_id',
                ),
            ]);

        $providerResponse =
            $this->withOrganization($provider)
                ->getJson(
                    self::INDEX_URL.
                    '?sort_by=calculated_at'.
                    '&sort_dir=asc'.
                    '&per_page=100',
                );

        $providerResponse
            ->assertOk()
            ->assertJsonCount(
                2,
                'data.items',
            )
            ->assertJsonPath(
                'data.items.0.public_id',
                (string) $older->getAttribute('public_id'),
            )
            ->assertJsonPath(
                'data.items.1.public_id',
                (string) $newer->getAttribute('public_id'),
            )
            ->assertJsonMissing([
                'public_id' => (string) $unrelated->getAttribute(
                    'public_id',
                ),
            ]);
    }

    public function test_show_returns_ordered_lines_and_hides_internal_ids(): void
    {
        $user = User::factory()->create();

        $customer = $this->createOrganization(
            Organization::TYPE_CARRIER,
        );

        $provider = $this->createOrganization(
            Organization::TYPE_SUBCONTRACTOR,
        );

        $relationship = $this->createRelationship(
            $customer,
            $provider,
        );

        $this->addMembership(
            $user,
            $customer,
        );

        $this->grantViewPermission(
            $user,
            $customer,
        );

        $fixture = $this->createCalculationFixture(
            customer: $customer,
            provider: $provider,
            relationship: $relationship,
            actor: $user,
        );

        $this->createLine(
            calculation: $fixture['calculation'],
            priceListItem: $fixture['deliveredItem'],
            pricingCode: FinancialCalculationLine::CODE_DELIVERED_PARCELS,
            quantity: '20.0000',
            unit: FinancialCalculationLine::UNIT_PARCEL,
            unitRate: '4.2500',
            lineAmount: '85.0000',
            position: 2,
        );

        $this->createLine(
            calculation: $fixture['calculation'],
            priceListItem: $fixture['actualKmItem'],
            pricingCode: FinancialCalculationLine::CODE_ACTUAL_KM,
            quantity: '8.1400',
            unit: FinancialCalculationLine::UNIT_KM,
            unitRate: '12.3456',
            lineAmount: '100.4932',
            position: 1,
        );

        $unrelatedCustomer = $this->createOrganization(
            Organization::TYPE_CARRIER,
        );

        $unrelatedProvider = $this->createOrganization(
            Organization::TYPE_SUBCONTRACTOR,
        );

        $unrelatedRelationship =
            $this->createRelationship(
                $unrelatedCustomer,
                $unrelatedProvider,
            );

        $unrelated = $this->createCalculationFixture(
            customer: $unrelatedCustomer,
            provider: $unrelatedProvider,
            relationship: $unrelatedRelationship,
            actor: $user,
        )['calculation'];

        Sanctum::actingAs($user);

        $response =
            $this->withOrganization($customer)
                ->getJson(
                    self::INDEX_URL.'/'.
                    $fixture['calculation']->getRouteKey(),
                );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.public_id',
                (string) $fixture['calculation']->getAttribute(
                    'public_id',
                ),
            )
            ->assertJsonPath(
                'data.daily_report_public_id',
                (string) $fixture['dailyReport']->getAttribute(
                    'public_id',
                ),
            )
            ->assertJsonPath(
                'data.price_list_public_id',
                (string) $fixture['priceList']->getAttribute(
                    'public_id',
                ),
            )
            ->assertJsonPath(
                'data.price_list_version',
                1,
            )
            ->assertJsonCount(
                2,
                'data.lines',
            )
            ->assertJsonPath(
                'data.lines.0.pricing_code',
                FinancialCalculationLine::CODE_ACTUAL_KM,
            )
            ->assertJsonPath(
                'data.lines.0.position',
                1,
            )
            ->assertJsonPath(
                'data.lines.1.pricing_code',
                FinancialCalculationLine::CODE_DELIVERED_PARCELS,
            )
            ->assertJsonPath(
                'data.lines.1.position',
                2,
            );

        $payload = $response->json('data');

        if (! is_array($payload)) {
            self::fail(
                'Financial calculation detail payload must be an array.',
            );
        }

        $this->assertNoInternalDatabaseIdentifiers(
            $payload,
        );

        $this->withOrganization($customer)
            ->getJson(
                self::INDEX_URL.'/'.
                $unrelated->getRouteKey(),
            )
            ->assertNotFound();
    }

    public function test_events_are_ordered_and_cannot_escape_scope(): void
    {
        $user = User::factory()->create();

        $customer = $this->createOrganization(
            Organization::TYPE_CARRIER,
        );

        $provider = $this->createOrganization(
            Organization::TYPE_SUBCONTRACTOR,
        );

        $relationship = $this->createRelationship(
            $customer,
            $provider,
        );

        $this->addMembership(
            $user,
            $provider,
        );

        $this->grantViewPermission(
            $user,
            $provider,
        );

        $fixture = $this->createCalculationFixture(
            customer: $customer,
            provider: $provider,
            relationship: $relationship,
            actor: $user,
        );

        $this->createEvent(
            calculation: $fixture['calculation'],
            provider: $provider,
            actor: $user,
            eventType: FinancialCalculationEvent::TYPE_REVIEW_STARTED,
            fromStatus: FinancialCalculation::STATUS_CALCULATED,
            toStatus: FinancialCalculation::STATUS_UNDER_REVIEW,
            createdAt: '2026-07-29 11:00:00',
        );

        $this->createEvent(
            calculation: $fixture['calculation'],
            provider: $provider,
            actor: $user,
            eventType: FinancialCalculationEvent::TYPE_CALCULATED,
            fromStatus: null,
            toStatus: FinancialCalculation::STATUS_CALCULATED,
            createdAt: '2026-07-29 10:00:00',
        );

        $unrelatedCustomer = $this->createOrganization(
            Organization::TYPE_CARRIER,
        );

        $unrelatedProvider = $this->createOrganization(
            Organization::TYPE_SUBCONTRACTOR,
        );

        $unrelatedRelationship =
            $this->createRelationship(
                $unrelatedCustomer,
                $unrelatedProvider,
            );

        $unrelated = $this->createCalculationFixture(
            customer: $unrelatedCustomer,
            provider: $unrelatedProvider,
            relationship: $unrelatedRelationship,
            actor: $user,
        )['calculation'];

        Sanctum::actingAs($user);

        $response =
            $this->withOrganization($provider)
                ->getJson(
                    self::INDEX_URL.'/'.
                    $fixture['calculation']->getRouteKey().
                    '/events',
                );

        $response
            ->assertOk()
            ->assertJsonCount(
                2,
                'data.items',
            )
            ->assertJsonPath(
                'data.items.0.event_type',
                FinancialCalculationEvent::TYPE_CALCULATED,
            )
            ->assertJsonPath(
                'data.items.0.from_status',
                null,
            )
            ->assertJsonPath(
                'data.items.0.to_status',
                FinancialCalculation::STATUS_CALCULATED,
            )
            ->assertJsonPath(
                'data.items.1.event_type',
                FinancialCalculationEvent::TYPE_REVIEW_STARTED,
            )
            ->assertJsonPath(
                'data.items.1.from_status',
                FinancialCalculation::STATUS_CALCULATED,
            )
            ->assertJsonPath(
                'data.items.1.to_status',
                FinancialCalculation::STATUS_UNDER_REVIEW,
            );

        $payload = $response->json('data.items');

        if (! is_array($payload)) {
            self::fail(
                'Financial calculation event payload must be an array.',
            );
        }

        $this->assertNoInternalDatabaseIdentifiers(
            $payload,
        );

        $this->withOrganization($provider)
            ->getJson(
                self::INDEX_URL.'/'.
                $unrelated->getRouteKey().
                '/events',
            )
            ->assertNotFound();
    }

    public function test_index_query_validation_is_enforced(): void
    {
        $user = User::factory()->create();

        $organization = $this->createOrganization(
            Organization::TYPE_CARRIER,
        );

        $this->addMembership(
            $user,
            $organization,
        );

        $this->grantViewPermission(
            $user,
            $organization,
        );

        Sanctum::actingAs($user);

        $this->withOrganization($organization)
            ->getJson(
                self::INDEX_URL.
                '?status=invalid'.
                '&currency=cz'.
                '&sort_by=id'.
                '&sort_dir=sideways'.
                '&per_page=101',
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'status',
                'currency',
                'sort_by',
                'sort_dir',
                'per_page',
            ]);
    }

    private function addMembership(
        User $user,
        Organization $organization,
    ): void {
        OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);
    }

    private function createOrganization(
        string $type,
    ): Organization {
        return Organization::query()->create([
            'name' => 'Read API organization '.Str::uuid(),
            'type' => $type,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function createRelationship(
        Organization $customer,
        Organization $provider,
        mixed $validUntil = null,
    ): OrganizationRelationship {
        return OrganizationRelationship::query()->create([
            'source_organization_id' => $customer->getKey(),
            'target_organization_id' => $provider->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => now()->subMonth(),
            'valid_until' => $validUntil,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{
     *     calculation: FinancialCalculation,
     *     dailyReport: DailyReport,
     *     priceList: PriceList,
     *     priceListVersion: PriceListVersion,
     *     actualKmItem: PriceListItem,
     *     deliveredItem: PriceListItem
     * }
     */
    private function createCalculationFixture(
        Organization $customer,
        Organization $provider,
        OrganizationRelationship $relationship,
        User $actor,
        array $overrides = [],
    ): array {
        $driver = Driver::query()->firstOrCreate(
            [
                'user_id' => $actor->getKey(),
            ],
            [
                'first_name' => 'Calculation',
                'last_name' => 'Reader',
                'phone' => null,
                'email' => null,
                'license_number' => 'READ-'.Str::uuid(),
                'license_category' => 'B',
                'active' => true,
            ],
        );

        $routeNumber =
            'READ-'.Str::upper(
                Str::random(16),
            );

        $dailyReport = DailyReport::query()->create([
            'organization_id' => $customer->getKey(),
            'trip_id' => null,
            'performed_by_driver_id' => $driver->getKey(),
            'vehicle_id' => null,
            'entered_by_user_id' => $actor->getKey(),
            'route_number' => $routeNumber,
            'route_number_normalized' => Str::lower(
                $routeNumber,
            ),
            'service_date' => '2026-07-29',
            'status' => DailyReport::STATUS_APPROVED,
            'entry_method' => DailyReport::ENTRY_METHOD_DRIVER,
            'entered_on_behalf' => false,
            'completion_confirmed_at' => '2026-07-29 09:00:00',
            'delivered_parcels' => 20,
            'redirected_parcels' => 2,
            'undelivered_parcels' => 1,
            'planned_km' => '8.00',
            'actual_km' => '8.14',
            'actual_km_source' => 'delivery_application',
            'operational_notes' => 'Financial calculation read API test',
            'current_version' => 3,
            'submitted_at' => '2026-07-29 09:05:00',
            'review_started_at' => '2026-07-29 09:10:00',
            'reviewed_by_user_id' => $actor->getKey(),
            'approved_at' => '2026-07-29 09:15:00',
            'approved_by_user_id' => $actor->getKey(),
            'closed_at' => null,
        ]);

        $priceList = PriceList::query()->create([
            'organization_relationship_id' => $relationship->getKey(),
            'owner_organization_id' => $customer->getKey(),
            'customer_organization_id' => $customer->getKey(),
            'provider_organization_id' => $provider->getKey(),
            'name' => 'Read API pricing '.Str::uuid(),
            'description' => 'Financial calculation read API pricing',
            'currency' => 'CZK',
            'status' => PriceList::STATUS_ACTIVE,
            'current_version' => 1,
            'created_by_user_id' => $actor->getKey(),
        ]);

        $priceListVersion =
            $priceList->versions()->create([
                'version_number' => 1,
                'status' => PriceListVersion::STATUS_ACTIVE,
                'valid_from' => '2026-07-01',
                'valid_until' => null,
                'change_reason' => 'Read API active pricing',
                'created_by_user_id' => $actor->getKey(),
                'approved_by_user_id' => $actor->getKey(),
                'approved_at' => '2026-06-30 10:00:00',
                'activated_at' => '2026-07-01 00:00:00',
            ]);

        $actualKmItem =
            $priceListVersion->items()->create([
                'code' => PriceListItem::CODE_ACTUAL_KM,
                'description' => 'Actual kilometres',
                'unit' => PriceListItem::UNIT_KM,
                'unit_rate' => '12.3456',
                'currency' => 'CZK',
                'quantity_source' => PriceListItem::QUANTITY_SOURCE_ACTUAL_KM,
                'position' => 1,
            ]);

        $deliveredItem =
            $priceListVersion->items()->create([
                'code' => PriceListItem::CODE_DELIVERED_PARCELS,
                'description' => 'Delivered parcels',
                'unit' => PriceListItem::UNIT_PARCEL,
                'unit_rate' => '4.2500',
                'currency' => 'CZK',
                'quantity_source' => PriceListItem::QUANTITY_SOURCE_DELIVERED_PARCELS,
                'position' => 2,
            ]);

        $calculation =
            FinancialCalculation::query()->create(
                array_merge(
                    [
                        'organization_id' => $provider->getKey(),
                        'organization_relationship_id' => $relationship->getKey(),
                        'price_list_id' => $priceList->getKey(),
                        'price_list_version_id' => $priceListVersion->getKey(),
                        'daily_report_id' => $dailyReport->getKey(),
                        'daily_report_version' => 3,
                        'status' => FinancialCalculation::STATUS_CALCULATED,
                        'currency' => 'CZK',
                        'input_snapshot' => [
                            'delivered_parcels' => 20,
                            'redirected_parcels' => 2,
                            'undelivered_parcels' => 1,
                            'actual_km' => '8.14',
                        ],
                        'calculation_version' => 1,
                        'subtotal_amount' => '185.49',
                        'total_amount' => '185.49',
                        'calculated_by_user_id' => $actor->getKey(),
                        'calculated_at' => '2026-07-29 10:00:00',
                    ],
                    $overrides,
                ),
            );

        return [
            'calculation' => $calculation,
            'dailyReport' => $dailyReport,
            'priceList' => $priceList,
            'priceListVersion' => $priceListVersion,
            'actualKmItem' => $actualKmItem,
            'deliveredItem' => $deliveredItem,
        ];
    }

    private function createLine(
        FinancialCalculation $calculation,
        PriceListItem $priceListItem,
        string $pricingCode,
        string $quantity,
        string $unit,
        string $unitRate,
        string $lineAmount,
        int $position,
    ): FinancialCalculationLine {
        return $calculation->lines()->create([
            'price_list_item_id' => $priceListItem->getKey(),
            'pricing_code' => $pricingCode,
            'quantity' => $quantity,
            'unit' => $unit,
            'unit_rate' => $unitRate,
            'currency' => 'CZK',
            'line_amount' => $lineAmount,
            'source_field' => $pricingCode,
            'rounding_scale' => 4,
            'rounding_method' => 'half_up',
            'position' => $position,
        ]);
    }

    private function createEvent(
        FinancialCalculation $calculation,
        Organization $provider,
        User $actor,
        string $eventType,
        ?string $fromStatus,
        string $toStatus,
        string $createdAt,
    ): FinancialCalculationEvent {
        return $calculation->events()->create([
            'organization_id' => $provider->getKey(),
            'event_type' => $eventType,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'acted_by_user_id' => $actor->getKey(),
            'reason' => null,
            'metadata' => [
                'source' => 'read-api-test',
            ],
            'created_at' => $createdAt,
        ]);
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
                'compensation.view',
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

    private function withOrganization(
        Organization $organization,
    ): static {
        return $this->withHeader(
            'X-Organization-ID',
            (string) $organization->getKey(),
        );
    }

    /**
     * @param  array<mixed>  $payload
     */
    private function assertNoInternalDatabaseIdentifiers(
        array $payload,
    ): void {
        $forbiddenKeys = [
            'id',
            'organization_id',
            'organization_relationship_id',
            'price_list_id',
            'price_list_version_id',
            'daily_report_id',
            'calculated_by_user_id',
            'approved_by_user_id',
            'supersedes_calculation_id',
            'financial_calculation_id',
            'price_list_item_id',
            'acted_by_user_id',
        ];

        foreach ($payload as $key => $value) {
            if (is_string($key)) {
                self::assertNotContains(
                    $key,
                    $forbiddenKeys,
                    'Internal database identifier was exposed: '.$key,
                );
            }

            if (is_array($value)) {
                $this->assertNoInternalDatabaseIdentifiers(
                    $value,
                );
            }
        }
    }
}
