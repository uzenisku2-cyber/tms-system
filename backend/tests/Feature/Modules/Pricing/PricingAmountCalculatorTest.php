<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Data\CalculatedPriceLine;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListItem;
use App\Modules\Pricing\Models\PriceListVersion;
use App\Modules\Pricing\Services\PricingAmountCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

final class PricingAmountCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_ordered_lines_with_exact_decimal_rounding(): void
    {
        $foundation = $this->createFoundation();
        $version = $foundation['version'];

        $this->createItem(
            version: $version,
            code: PriceListItem::CODE_ACTUAL_KM,
            unit: PriceListItem::UNIT_KM,
            unitRate: '12.3456',
            quantitySource: PriceListItem::QUANTITY_SOURCE_ACTUAL_KM,
            position: 4,
        );

        $this->createItem(
            version: $version,
            code: PriceListItem::CODE_UNDELIVERED_PARCELS,
            unit: PriceListItem::UNIT_PARCEL,
            unitRate: '7.0000',
            quantitySource: PriceListItem::QUANTITY_SOURCE_UNDELIVERED_PARCELS,
            position: 3,
        );

        $this->createItem(
            version: $version,
            code: PriceListItem::CODE_DELIVERED_PARCELS,
            unit: PriceListItem::UNIT_PARCEL,
            unitRate: '4.2500',
            quantitySource: PriceListItem::QUANTITY_SOURCE_DELIVERED_PARCELS,
            position: 1,
        );

        $this->createItem(
            version: $version,
            code: PriceListItem::CODE_REDIRECTED_PARCELS,
            unit: PriceListItem::UNIT_PARCEL,
            unitRate: '1.0050',
            quantitySource: PriceListItem::QUANTITY_SOURCE_REDIRECTED_PARCELS,
            position: 2,
        );

        $snapshot = [
            'delivered_parcels' => 3,
            'redirected_parcels' => 2,
            'undelivered_parcels' => 0,
            'actual_km' => '8.145',
            'planned_km' => '9.000',
        ];

        $result = $this->calculator()->calculate(
            $version,
            $snapshot,
        );

        self::assertSame('CZK', $result->currency);
        self::assertSame('115.31', $result->subtotalAmount);
        self::assertSame('115.31', $result->totalAmount);
        self::assertSame($snapshot, $result->inputSnapshot);
        self::assertCount(4, $result->lines);

        self::assertSame(
            [
                PriceListItem::CODE_DELIVERED_PARCELS,
                PriceListItem::CODE_REDIRECTED_PARCELS,
                PriceListItem::CODE_UNDELIVERED_PARCELS,
                PriceListItem::CODE_ACTUAL_KM,
            ],
            array_map(
                static fn (
                    CalculatedPriceLine $line,
                ): string => $line->pricingCode,
                $result->lines,
            ),
        );

        $linesByCode = [];

        foreach ($result->lines as $line) {
            $linesByCode[$line->pricingCode] = $line;
        }

        $delivered =
            $linesByCode[PriceListItem::CODE_DELIVERED_PARCELS];

        self::assertSame('3.000', $delivered->quantity);
        self::assertSame('4.2500', $delivered->unitRate);
        self::assertSame('12.75', $delivered->lineAmount);
        self::assertSame(1, $delivered->position);

        $redirected =
            $linesByCode[PriceListItem::CODE_REDIRECTED_PARCELS];

        self::assertSame('2.000', $redirected->quantity);
        self::assertSame('1.0050', $redirected->unitRate);
        self::assertSame('2.01', $redirected->lineAmount);
        self::assertSame(2, $redirected->position);

        $undelivered =
            $linesByCode[PriceListItem::CODE_UNDELIVERED_PARCELS];

        self::assertSame('0.000', $undelivered->quantity);
        self::assertSame('7.0000', $undelivered->unitRate);
        self::assertSame('0.00', $undelivered->lineAmount);
        self::assertSame(3, $undelivered->position);

        $actualKilometres =
            $linesByCode[PriceListItem::CODE_ACTUAL_KM];

        self::assertSame('8.145', $actualKilometres->quantity);
        self::assertSame('12.3456', $actualKilometres->unitRate);
        self::assertSame('100.55', $actualKilometres->lineAmount);
        self::assertSame(4, $actualKilometres->position);

        $payload = $result->toArray();

        self::assertSame('CZK', $payload['currency']);
        self::assertSame('115.31', $payload['subtotal_amount']);
        self::assertSame('115.31', $payload['total_amount']);
        self::assertCount(4, $payload['lines']);

        self::assertSame(
            '0.00',
            $payload['lines'][2]['line_amount'],
        );

        self::assertSame(
            PriceListItem::ROUNDING_METHOD_HALF_UP,
            $payload['lines'][3]['rounding_method'],
        );
    }

    public function test_it_rejects_a_missing_snapshot_quantity_source(): void
    {
        $foundation = $this->createFoundation();

        $this->createItem(
            version: $foundation['version'],
            code: PriceListItem::CODE_DELIVERED_PARCELS,
            unit: PriceListItem::UNIT_PARCEL,
            unitRate: '4.2500',
            quantitySource: PriceListItem::QUANTITY_SOURCE_DELIVERED_PARCELS,
            position: 1,
        );

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'Input snapshot is missing quantity source [delivered_parcels].',
        );

        $this->calculator()->calculate(
            $foundation['version'],
            [
                'redirected_parcels' => 1,
                'undelivered_parcels' => 0,
                'actual_km' => '5.000',
            ],
        );
    }

    public function test_it_rejects_an_item_currency_different_from_the_price_list(): void
    {
        $foundation = $this->createFoundation();

        $this->createItem(
            version: $foundation['version'],
            code: PriceListItem::CODE_DELIVERED_PARCELS,
            unit: PriceListItem::UNIT_PARCEL,
            unitRate: '4.2500',
            quantitySource: PriceListItem::QUANTITY_SOURCE_DELIVERED_PARCELS,
            position: 1,
            currency: 'EUR',
        );

        $this->expectException(LogicException::class);

        $this->expectExceptionMessage(
            'Pricing code [delivered_parcels] uses currency [EUR] instead of [CZK].',
        );

        $this->calculator()->calculate(
            $foundation['version'],
            [
                'delivered_parcels' => 1,
            ],
        );
    }

    /**
     * @return array{
     *     customer: Organization,
     *     provider: Organization,
     *     relationship: OrganizationRelationship,
     *     user: User,
     *     priceList: PriceList,
     *     version: PriceListVersion
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

        $relationship =
            OrganizationRelationship::query()->create([
                'source_organization_id' => $customer->getKey(),

                'target_organization_id' => $provider->getKey(),

                'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,

                'status' => OrganizationRelationship::STATUS_ACTIVE,

                'valid_from' => now()->subMonth(),
                'valid_until' => null,
            ]);

        $user = User::factory()->create();

        $priceList = PriceList::query()->create([
            'organization_relationship_id' => $relationship->getKey(),

            'owner_organization_id' => $customer->getKey(),

            'customer_organization_id' => $customer->getKey(),

            'provider_organization_id' => $provider->getKey(),

            'name' => 'Pricing calculator test '.Str::uuid(),

            'description' => 'Deterministic calculator test pricing',

            'currency' => 'CZK',
            'status' => PriceList::STATUS_ACTIVE,
            'current_version' => 1,
            'created_by_user_id' => $user->getKey(),
        ]);

        $version = $priceList->versions()->create([
            'version_number' => 1,
            'status' => PriceListVersion::STATUS_ACTIVE,
            'valid_from' => '2026-07-01',
            'valid_until' => null,
            'change_reason' => 'Calculator test version',
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
            'priceList' => $priceList,
            'version' => $version,
        ];
    }

    private function createItem(
        PriceListVersion $version,
        string $code,
        string $unit,
        string $unitRate,
        string $quantitySource,
        int $position,
        string $currency = 'CZK',
    ): PriceListItem {
        return $version->items()->create([
            'code' => $code,
            'description' => 'Calculator item '.$code,
            'calculation_method' => PriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE,

            'unit' => $unit,
            'unit_rate' => $unitRate,
            'currency' => $currency,
            'quantity_source' => $quantitySource,
            'rounding_scale' => 2,
            'rounding_method' => PriceListItem::ROUNDING_METHOD_HALF_UP,

            'position' => $position,
        ]);
    }

    private function calculator(): PricingAmountCalculator
    {
        return new PricingAmountCalculator;
    }

    private function createOrganization(
        string $type,
    ): Organization {
        return Organization::query()->create([
            'name' => 'Calculator organization '.Str::uuid(),

            'type' => $type,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }
}
