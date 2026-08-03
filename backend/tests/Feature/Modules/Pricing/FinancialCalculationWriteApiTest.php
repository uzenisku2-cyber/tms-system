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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FinancialCalculationWriteApiTest extends TestCase
{
    use RefreshDatabase;

    private const STORE_URL =
        '/api/v1/financial-calculations';

    protected function setUp(): void
    {
        parent::setUp();

        app(OrganizationContext::class)->clear();

        $registrar = app(PermissionRegistrar::class);

        $registrar->setPermissionsTeamId(null);
        $registrar->forgetCachedPermissions();
    }

    protected function tearDown(): void
    {
        app(OrganizationContext::class)->clear();

        $registrar = app(PermissionRegistrar::class);

        $registrar->setPermissionsTeamId(null);
        $registrar->forgetCachedPermissions();

        parent::tearDown();
    }

    public function test_guest_cannot_create_financial_calculation(): void
    {
        $this->postJson(
            self::STORE_URL,
            [],
        )->assertUnauthorized();

        $this->assertNoFinancialRecords();
    }

    public function test_organization_context_is_required(): void
    {
        $foundation = $this->createFoundation(true);

        Sanctum::actingAs($foundation['user']);

        $this->postJson(
            self::STORE_URL,
            $this->payload($foundation),
        )->assertStatus(400);

        $this->assertNoFinancialRecords();
    }

    public function test_compensation_manage_permission_is_required(): void
    {
        $foundation = $this->createFoundation(false);

        Sanctum::actingAs($foundation['user']);

        $this->withOrganization($foundation['provider'])
            ->postJson(
                self::STORE_URL,
                $this->payload($foundation),
            )
            ->assertForbidden();

        $this->assertNoFinancialRecords();
    }

    public function test_creation_payload_is_validated(): void
    {
        $foundation = $this->createFoundation(true);

        Sanctum::actingAs($foundation['user']);

        $this->withOrganization($foundation['provider'])
            ->postJson(
                self::STORE_URL,
                [
                    'daily_report_public_id' => 'invalid',
                    'daily_report_version' => 0,
                    'price_list_public_id' => 'invalid',
                    'price_list_version' => 0,
                    'reason' => str_repeat('x', 2001),
                ],
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'daily_report_public_id',
                'daily_report_version',
                'price_list_public_id',
                'price_list_version',
                'reason',
            ]);

        $this->assertNoFinancialRecords();
    }

    public function test_it_creates_initial_calculation_atomically(): void
    {
        $foundation = $this->createFoundation(true);

        Sanctum::actingAs($foundation['user']);

        $response = $this
            ->withOrganization($foundation['provider'])
            ->postJson(
                self::STORE_URL,
                $this->payload(
                    $foundation,
                    '  Initial API calculation.  ',
                ),
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Financial calculation created.',
            )
            ->assertJsonPath(
                'data.status',
                FinancialCalculation::STATUS_CALCULATED,
            )
            ->assertJsonPath(
                'data.currency',
                'CZK',
            )
            ->assertJsonPath(
                'data.daily_report_public_id',
                (string) $foundation['dailyReport']->getAttribute(
                    'public_id',
                ),
            )
            ->assertJsonPath(
                'data.daily_report_version',
                4,
            )
            ->assertJsonPath(
                'data.price_list_public_id',
                (string) $foundation['priceList']->getAttribute(
                    'public_id',
                ),
            )
            ->assertJsonPath(
                'data.price_list_version',
                1,
            )
            ->assertJsonPath(
                'data.calculation_version',
                1,
            )
            ->assertJsonPath(
                'data.subtotal_amount',
                '194.56',
            )
            ->assertJsonPath(
                'data.total_amount',
                '194.56',
            )
            ->assertJsonCount(
                4,
                'data.lines',
            );

        $publicId =
            $response->json('data.public_id');

        self::assertIsString($publicId);
        self::assertTrue(Str::isUuid($publicId));

        $payload =
            $response->json('data');

        self::assertIsArray($payload);

        $this->assertNoInternalDatabaseIdentifiers(
            $payload,
        );

        $calculation = FinancialCalculation::query()
            ->where(
                'public_id',
                $publicId,
            )
            ->sole();

        self::assertSame(
            $foundation['provider']->getKey(),
            $calculation->getAttribute('organization_id'),
        );

        self::assertSame(
            $foundation['relationship']->getKey(),
            $calculation->getAttribute(
                'organization_relationship_id',
            ),
        );

        self::assertSame(
            $foundation['dailyReport']->getKey(),
            $calculation->getAttribute('daily_report_id'),
        );

        self::assertSame(
            $foundation['priceListVersion']->getKey(),
            $calculation->getAttribute(
                'price_list_version_id',
            ),
        );

        self::assertSame(
            $foundation['user']->getKey(),
            $calculation->getAttribute(
                'calculated_by_user_id',
            ),
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

        $this->assertDatabaseHas(
            'financial_calculation_lines',
            [
                'financial_calculation_id' => $calculation->getKey(),
                'pricing_code' => PriceListItem::CODE_DELIVERED_PARCELS,
                'quantity' => '20.000',
                'unit_rate' => '4.2500',
                'line_amount' => '85.00',
                'position' => 1,
            ],
        );

        $this->assertDatabaseHas(
            'financial_calculation_lines',
            [
                'financial_calculation_id' => $calculation->getKey(),
                'pricing_code' => PriceListItem::CODE_REDIRECTED_PARCELS,
                'quantity' => '2.000',
                'unit_rate' => '1.0050',
                'line_amount' => '2.01',
                'position' => 2,
            ],
        );

        $this->assertDatabaseHas(
            'financial_calculation_lines',
            [
                'financial_calculation_id' => $calculation->getKey(),
                'pricing_code' => PriceListItem::CODE_UNDELIVERED_PARCELS,
                'quantity' => '1.000',
                'unit_rate' => '7.0000',
                'line_amount' => '7.00',
                'position' => 3,
            ],
        );

        $this->assertDatabaseHas(
            'financial_calculation_lines',
            [
                'financial_calculation_id' => $calculation->getKey(),
                'pricing_code' => PriceListItem::CODE_ACTUAL_KM,
                'quantity' => '8.145',
                'unit_rate' => '12.3456',
                'line_amount' => '100.55',
                'position' => 4,
            ],
        );

        $this->assertDatabaseHas(
            'financial_calculation_events',
            [
                'financial_calculation_id' => $calculation->getKey(),
                'organization_id' => $foundation['provider']->getKey(),
                'event_type' => FinancialCalculationEvent::TYPE_CALCULATED,
                'from_status' => null,
                'to_status' => FinancialCalculation::STATUS_CALCULATED,
                'acted_by_user_id' => $foundation['user']->getKey(),
                'reason' => 'Initial API calculation.',
            ],
        );
    }

    public function test_unavailable_price_list_is_not_found_atomically(): void
    {
        $foundation = $this->createFoundation(true);

        Sanctum::actingAs($foundation['user']);

        $payload = $this->payload($foundation);
        $payload['price_list_public_id'] = (string) Str::uuid();

        $this->withOrganization($foundation['provider'])
            ->postJson(
                self::STORE_URL,
                $payload,
            )
            ->assertNotFound();

        $this->assertNoFinancialRecords();
    }

    public function test_duplicate_initial_calculation_is_rejected_as_conflict_atomically(): void
    {
        $foundation = $this->createFoundation(true);

        Sanctum::actingAs($foundation['user']);

        $payload = $this->payload($foundation);

        $this->withOrganization($foundation['provider'])
            ->postJson(
                self::STORE_URL,
                $payload,
            )
            ->assertCreated();

        $this->withOrganization($foundation['provider'])
            ->postJson(
                self::STORE_URL,
                $payload,
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'The daily-report version has already been calculated.',
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
                'valid_from' => '2026-07-01',
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
            $this->grantManagePermission(
                $user,
                $provider,
            );
        }

        $driver = Driver::query()->create([
            'user_id' => $user->getKey(),
            'first_name' => 'Creation',
            'last_name' => 'Driver',
            'phone' => null,
            'email' => null,
            'license_number' => 'CREATE-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);

        $routeNumber =
            'CREATE-'.Str::upper(
                Str::random(12),
            );

        $dailyReport = DailyReport::query()->create([
            'organization_id' => $customer->getKey(),
            'trip_id' => null,
            'performed_by_driver_id' => $driver->getKey(),
            'vehicle_id' => null,
            'entered_by_user_id' => $user->getKey(),
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
            'planned_km' => '9.000',
            'actual_km' => '8.145',
            'actual_km_source' => DailyReport::ACTUAL_KM_SOURCE_DELIVERY_APPLICATION,
            'operational_notes' => 'Financial creation API test',
            'current_version' => 4,
            'submitted_at' => '2026-07-29 09:05:00',
            'review_started_at' => '2026-07-29 09:10:00',
            'reviewed_by_user_id' => $user->getKey(),
            'approved_at' => '2026-07-29 09:15:00',
            'approved_by_user_id' => $user->getKey(),
            'closed_at' => null,
        ]);

        $dailyReportVersion =
            DailyReportVersion::query()->create([
                'daily_report_id' => $dailyReport->getKey(),
                'version_number' => 4,
                'snapshot' => [
                    'public_id' => (string) $dailyReport->getAttribute(
                        'public_id',
                    ),
                    'organization_id' => $customer->getKey(),
                    'trip_id' => null,
                    'performed_by_driver_id' => $driver->getKey(),
                    'vehicle_id' => null,
                    'route_number' => $routeNumber,
                    'route_number_normalized' => Str::lower(
                        $routeNumber,
                    ),
                    'service_date' => '2026-07-29',
                    'status' => DailyReport::STATUS_APPROVED,
                    'delivered_parcels' => 20,
                    'redirected_parcels' => 2,
                    'undelivered_parcels' => 1,
                    'planned_km' => '9.000',
                    'actual_km' => '8.145',
                    'actual_km_source' => DailyReport::ACTUAL_KM_SOURCE_DELIVERY_APPLICATION,
                    'current_version' => 4,
                    'approved_at' => '2026-07-29 09:15:00',
                    'approved_by_user_id' => $user->getKey(),
                    'closed_at' => null,
                ],
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
            'name' => 'Financial creation pricing '.Str::uuid(),
            'description' => 'Financial creation API test pricing',
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
                'change_reason' => 'Financial creation active pricing',
                'created_by_user_id' => $user->getKey(),
                'approved_by_user_id' => $user->getKey(),
                'approved_at' => '2026-06-30 10:00:00',
                'activated_at' => '2026-07-01 00:00:00',
            ]);

        $this->createPriceListItem(
            $priceListVersion,
            PriceListItem::CODE_DELIVERED_PARCELS,
            PriceListItem::UNIT_PARCEL,
            '4.2500',
            PriceListItem::QUANTITY_SOURCE_DELIVERED_PARCELS,
            1,
        );

        $this->createPriceListItem(
            $priceListVersion,
            PriceListItem::CODE_REDIRECTED_PARCELS,
            PriceListItem::UNIT_PARCEL,
            '1.0050',
            PriceListItem::QUANTITY_SOURCE_REDIRECTED_PARCELS,
            2,
        );

        $this->createPriceListItem(
            $priceListVersion,
            PriceListItem::CODE_UNDELIVERED_PARCELS,
            PriceListItem::UNIT_PARCEL,
            '7.0000',
            PriceListItem::QUANTITY_SOURCE_UNDELIVERED_PARCELS,
            3,
        );

        $this->createPriceListItem(
            $priceListVersion,
            PriceListItem::CODE_ACTUAL_KM,
            PriceListItem::UNIT_KM,
            '12.3456',
            PriceListItem::QUANTITY_SOURCE_ACTUAL_KM,
            4,
        );

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

    private function createPriceListItem(
        PriceListVersion $version,
        string $code,
        string $unit,
        string $unitRate,
        string $quantitySource,
        int $position,
    ): PriceListItem {
        return $version->items()->create([
            'code' => $code,
            'description' => 'Creation API item '.$code,
            'calculation_method' => PriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE,
            'unit' => $unit,
            'unit_rate' => $unitRate,
            'currency' => 'CZK',
            'quantity_source' => $quantitySource,
            'rounding_scale' => 2,
            'rounding_method' => PriceListItem::ROUNDING_METHOD_HALF_UP,
            'position' => $position,
        ]);
    }

    private function createOrganization(
        string $type,
    ): Organization {
        return Organization::query()->create([
            'name' => 'Creation API organization '.Str::uuid(),
            'type' => $type,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function grantManagePermission(
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
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');

            $registrar->setPermissionsTeamId(
                $previousOrganizationId,
            );

            $registrar->forgetCachedPermissions();
        }
    }

    /**
     * @param array{
     *     dailyReport: DailyReport,
     *     priceList: PriceList
     * } $foundation
     * @return array<string, mixed>
     */
    private function payload(
        array $foundation,
        ?string $reason = null,
    ): array {
        return [
            'daily_report_public_id' => (
                (string) $foundation['dailyReport']->getAttribute(
                    'public_id',
                )
            ),
            'daily_report_version' => 4,
            'price_list_public_id' => (
                (string) $foundation['priceList']->getAttribute(
                    'public_id',
                )
            ),
            'price_list_version' => 1,
            'reason' => $reason,
        ];
    }

    private function withOrganization(
        Organization $organization,
    ): static {
        return $this->withHeader(
            'X-Organization-ID',
            (string) $organization->getKey(),
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
