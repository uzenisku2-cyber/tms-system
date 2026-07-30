<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Models\FinancialCalculationEvent;
use App\Modules\Pricing\Models\FinancialCalculationLine;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListItem;
use App\Modules\Pricing\Models\PriceListVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class FinancialCalculationModelFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_financial_calculation_graph_and_casts(): void
    {
        $foundation = $this->createFoundation();

        $calculation = $this->createCalculation(
            $foundation,
            [
                'subtotal_amount' => '100.50',
                'total_amount' => '100.50',
            ],
        );

        $line = $calculation->lines()->create([
            'price_list_item_id' => $foundation['actualKmItem']->getKey(),

            'pricing_code' => FinancialCalculationLine::CODE_ACTUAL_KM,

            'description' => 'Actual kilometres',
            'quantity' => '8.140',
            'unit' => FinancialCalculationLine::UNIT_KM,
            'unit_rate' => '12.3456',
            'currency' => 'CZK',
            'line_amount' => '100.50',
            'source_field' => PriceListItem::QUANTITY_SOURCE_ACTUAL_KM,
            'position' => 1,
        ]);

        $event = $calculation->events()->create([
            'organization_id' => $foundation['provider']->getKey(),

            'event_type' => FinancialCalculationEvent::TYPE_CALCULATED,

            'from_status' => null,
            'to_status' => FinancialCalculation::STATUS_CALCULATED,

            'acted_by_user_id' => $foundation['user']->getKey(),

            'reason' => 'Initial calculation',
            'metadata' => [
                'daily_report_version' => 3,
                'calculation_version' => 1,
            ],
        ]);

        $calculation->refresh();
        $line->refresh();
        $event->refresh();

        self::assertSame(
            'public_id',
            $calculation->getRouteKeyName(),
        );

        self::assertTrue(
            Str::isUuid(
                (string) $calculation->getAttribute('public_id'),
            ),
        );

        self::assertTrue(
            $calculation->organization->is(
                $foundation['provider'],
            ),
        );

        self::assertTrue(
            $calculation->organizationRelationship->is(
                $foundation['relationship'],
            ),
        );

        self::assertTrue(
            $calculation->priceList->is(
                $foundation['priceList'],
            ),
        );

        self::assertTrue(
            $calculation->priceListVersion->is(
                $foundation['priceListVersion'],
            ),
        );

        self::assertTrue(
            $calculation->dailyReport->is(
                $foundation['dailyReport'],
            ),
        );

        self::assertTrue(
            $calculation->calculatedBy->is(
                $foundation['user'],
            ),
        );

        self::assertTrue(
            $calculation->lines->contains($line),
        );

        self::assertTrue(
            $calculation->events->contains($event),
        );

        self::assertTrue(
            $line->financialCalculation->is(
                $calculation,
            ),
        );

        self::assertTrue(
            $line->priceListItem->is(
                $foundation['actualKmItem'],
            ),
        );

        self::assertTrue(
            $event->financialCalculation->is(
                $calculation,
            ),
        );

        self::assertTrue(
            $event->organization->is(
                $foundation['provider'],
            ),
        );

        self::assertTrue(
            $event->actedBy->is(
                $foundation['user'],
            ),
        );

        self::assertEquals(
            [
                'delivered_parcels' => 20,
                'redirected_parcels' => 2,
                'undelivered_parcels' => 1,
                'actual_km' => '8.14',
            ],
            $calculation->getAttribute('input_snapshot'),
        );

        self::assertSame(
            '100.50',
            $calculation->getAttribute('subtotal_amount'),
        );

        self::assertSame(
            '100.50',
            $calculation->getAttribute('total_amount'),
        );

        self::assertSame(
            '8.140',
            $line->getAttribute('quantity'),
        );

        self::assertSame(
            '12.3456',
            $line->getAttribute('unit_rate'),
        );

        self::assertSame(
            '100.50',
            $line->getAttribute('line_amount'),
        );

        self::assertEquals(
            [
                'daily_report_version' => 3,
                'calculation_version' => 1,
            ],
            $event->getAttribute('metadata'),
        );

        self::assertTrue(
            $event->isInitialCalculationEvent(),
        );

        $this->assertDatabaseHas(
            'financial_calculations',
            [
                'id' => $calculation->getKey(),
                'daily_report_version' => 3,
                'calculation_version' => 1,
                'status' => FinancialCalculation::STATUS_CALCULATED,
                'currency' => 'CZK',
                'total_amount' => '100.50',
            ],
        );

        $this->assertDatabaseHas(
            'financial_calculation_lines',
            [
                'id' => $line->getKey(),
                'pricing_code' => FinancialCalculationLine::CODE_ACTUAL_KM,
                'source_field' => PriceListItem::QUANTITY_SOURCE_ACTUAL_KM,
                'position' => 1,
            ],
        );
    }

    public function test_it_applies_defaults_status_helpers_scopes_and_superseding(): void
    {
        $foundation = $this->createFoundation();

        $initial = $this->createCalculation(
            $foundation,
        );

        $replacement = $this->createCalculation(
            $foundation,
            [
                'calculation_version' => 2,
                'status' => FinancialCalculation::STATUS_UNDER_REVIEW,

                'supersedes_calculation_id' => $initial->getKey(),
            ],
        );

        $initial->refresh();
        $replacement->refresh();

        self::assertSame(
            1,
            $initial->getAttribute('calculation_version'),
        );

        self::assertSame(
            '0.00',
            $initial->getAttribute('subtotal_amount'),
        );

        self::assertSame(
            '0.00',
            $initial->getAttribute('total_amount'),
        );

        self::assertTrue($initial->isCalculated());
        self::assertFalse($initial->isUnderReview());
        self::assertFalse($initial->isApproved());
        self::assertFalse($initial->isClosed());
        self::assertFalse($initial->isCancelled());

        self::assertFalse($replacement->isCalculated());
        self::assertTrue($replacement->isUnderReview());

        self::assertTrue(
            $replacement->supersedesCalculation->is(
                $initial,
            ),
        );

        self::assertTrue(
            $initial->supersededByCalculations->contains(
                $replacement,
            ),
        );

        self::assertSame(
            2,
            FinancialCalculation::query()
                ->forOrganization(
                    (int) $foundation['provider']->getKey(),
                )
                ->count(),
        );

        self::assertSame(
            0,
            FinancialCalculation::query()
                ->forOrganization(
                    (int) $foundation['customer']->getKey(),
                )
                ->count(),
        );

        self::assertSame(
            1,
            FinancialCalculation::query()
                ->withStatus(
                    FinancialCalculation::STATUS_CALCULATED,
                )
                ->count(),
        );

        self::assertSame(
            1,
            FinancialCalculation::query()
                ->withStatus(
                    FinancialCalculation::STATUS_UNDER_REVIEW,
                )
                ->count(),
        );
    }

    public function test_it_orders_lines_and_events_deterministically(): void
    {
        $foundation = $this->createFoundation();

        $calculation = $this->createCalculation(
            $foundation,
        );

        $deliveredLine = $calculation->lines()->create([
            'price_list_item_id' => $foundation['deliveredItem']->getKey(),

            'pricing_code' => FinancialCalculationLine::CODE_DELIVERED_PARCELS,

            'description' => 'Delivered parcels',
            'quantity' => '20.000',
            'unit' => FinancialCalculationLine::UNIT_PARCEL,
            'unit_rate' => '4.2500',
            'currency' => 'CZK',
            'line_amount' => '85.00',
            'source_field' => PriceListItem::QUANTITY_SOURCE_DELIVERED_PARCELS,

            'position' => 2,
        ]);

        $kilometreLine = $calculation->lines()->create([
            'price_list_item_id' => $foundation['actualKmItem']->getKey(),

            'pricing_code' => FinancialCalculationLine::CODE_ACTUAL_KM,

            'description' => 'Actual kilometres',
            'quantity' => '8.140',
            'unit' => FinancialCalculationLine::UNIT_KM,
            'unit_rate' => '12.3456',
            'currency' => 'CZK',
            'line_amount' => '100.50',
            'source_field' => PriceListItem::QUANTITY_SOURCE_ACTUAL_KM,

            'position' => 1,
        ]);

        $reviewEvent = $calculation->events()->create([
            'organization_id' => $foundation['provider']->getKey(),

            'event_type' => FinancialCalculationEvent::TYPE_REVIEW_STARTED,

            'from_status' => FinancialCalculation::STATUS_CALCULATED,

            'to_status' => FinancialCalculation::STATUS_UNDER_REVIEW,

            'acted_by_user_id' => $foundation['user']->getKey(),

            'reason' => 'Review started',
            'metadata' => null,
            'created_at' => '2026-07-29 11:00:00',
        ]);

        $initialEvent = $calculation->events()->create([
            'organization_id' => $foundation['provider']->getKey(),

            'event_type' => FinancialCalculationEvent::TYPE_CALCULATED,

            'from_status' => null,
            'to_status' => FinancialCalculation::STATUS_CALCULATED,

            'acted_by_user_id' => $foundation['user']->getKey(),

            'reason' => 'Initial calculation',
            'metadata' => null,
            'created_at' => '2026-07-29 10:00:00',
        ]);

        self::assertSame(
            [
                $kilometreLine->getKey(),
                $deliveredLine->getKey(),
            ],
            $calculation->lines()
                ->pluck('id')
                ->all(),
        );

        self::assertSame(
            [
                $initialEvent->getKey(),
                $reviewEvent->getKey(),
            ],
            $calculation->events()
                ->pluck('id')
                ->all(),
        );

        self::assertTrue($kilometreLine->isKilometreLine());
        self::assertFalse($kilometreLine->isParcelLine());

        self::assertTrue($deliveredLine->isParcelLine());
        self::assertFalse($deliveredLine->isKilometreLine());
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
     *     priceListVersion: PriceListVersion,
     *     actualKmItem: PriceListItem,
     *     deliveredItem: PriceListItem
     * }
     */
    private function createFoundation(): array
    {
        $customer = $this->createOrganization(
            Organization::TYPE_CARRIER,
        );

        $provider = $this->createOrganization(
            Organization::TYPE_SUBCONTRACTOR,
        );

        $relationship = OrganizationRelationship::query()->create([
            'source_organization_id' => $customer->getKey(),
            'target_organization_id' => $provider->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,

            'status' => OrganizationRelationship::STATUS_ACTIVE,

            'valid_from' => now()->subMonth(),
            'valid_until' => null,
        ]);

        $user = User::factory()->create();

        $driver = Driver::query()->create([
            'user_id' => $user->getKey(),
            'first_name' => 'Pricing',
            'last_name' => 'Driver',
            'phone' => null,
            'email' => null,
            'license_number' => 'PRICING-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);

        $routeNumber =
            'PRICING-'.Str::upper(
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
            'planned_km' => '8.00',
            'actual_km' => '8.14',
            'actual_km_source' => 'delivery_application',
            'operational_notes' => 'Pricing model foundation test',
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
            'name' => 'Financial model test pricing',
            'description' => 'Pricing used by model foundation tests',
            'currency' => 'CZK',
            'status' => PriceList::STATUS_ACTIVE,
            'current_version' => 1,
            'created_by_user_id' => $user->getKey(),
        ]);

        $priceListVersion = $priceList->versions()->create([
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

        $actualKmItem = $priceListVersion->items()->create([
            'code' => PriceListItem::CODE_ACTUAL_KM,
            'description' => 'Actual kilometres',
            'unit' => PriceListItem::UNIT_KM,
            'unit_rate' => '12.3456',
            'currency' => 'CZK',
            'quantity_source' => PriceListItem::QUANTITY_SOURCE_ACTUAL_KM,

            'position' => 1,
        ]);

        $deliveredItem = $priceListVersion->items()->create([
            'code' => PriceListItem::CODE_DELIVERED_PARCELS,
            'description' => 'Delivered parcels',
            'unit' => PriceListItem::UNIT_PARCEL,
            'unit_rate' => '4.2500',
            'currency' => 'CZK',
            'quantity_source' => PriceListItem::QUANTITY_SOURCE_DELIVERED_PARCELS,

            'position' => 2,
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
            'actualKmItem' => $actualKmItem,
            'deliveredItem' => $deliveredItem,
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
     *     priceListVersion: PriceListVersion,
     *     actualKmItem: PriceListItem,
     *     deliveredItem: PriceListItem
     * }  $foundation
     * @param  array<string, mixed>  $overrides
     */
    private function createCalculation(
        array $foundation,
        array $overrides = [],
    ): FinancialCalculation {
        return FinancialCalculation::query()->create(
            array_merge(
                [
                    'organization_id' => $foundation['provider']->getKey(),

                    'organization_relationship_id' => $foundation['relationship']->getKey(),

                    'price_list_id' => $foundation['priceList']->getKey(),

                    'price_list_version_id' => $foundation['priceListVersion']->getKey(),

                    'daily_report_id' => $foundation['dailyReport']->getKey(),

                    'daily_report_version' => 3,
                    'currency' => 'CZK',
                    'input_snapshot' => [
                        'delivered_parcels' => 20,
                        'redirected_parcels' => 2,
                        'undelivered_parcels' => 1,
                        'actual_km' => '8.14',
                    ],

                    'calculated_by_user_id' => $foundation['user']->getKey(),

                    'calculated_at' => '2026-07-29 10:00:00',
                ],
                $overrides,
            ),
        );
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
}
