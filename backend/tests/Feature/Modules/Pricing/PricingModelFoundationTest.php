<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListItem;
use App\Modules\Pricing\Models\PriceListVersion;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PricingModelFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_aggregate_relationships_and_public_identifier(): void
    {
        [
            $customer,
            $provider,
            $relationship,
            $user,
        ] = $this->createCommercialParties();

        $priceList = PriceList::query()->create([
            'organization_relationship_id' => $relationship->getKey(),
            'owner_organization_id' => $customer->getKey(),
            'customer_organization_id' => $customer->getKey(),
            'provider_organization_id' => $provider->getKey(),
            'name' => 'Standard partner pricing',
            'description' => 'Pricing foundation test',
            'currency' => 'CZK',
            'created_by_user_id' => $user->getKey(),
        ]);

        $version = $priceList->versions()->create([
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

        $item = $version->items()->create([
            'code' => PriceListItem::CODE_ACTUAL_KM,
            'description' => 'Actual kilometres',
            'unit' => PriceListItem::UNIT_KM,
            'unit_rate' => '12.3456',
            'currency' => 'CZK',
            'quantity_source' => PriceListItem::QUANTITY_SOURCE_ACTUAL_KM,
            'position' => 1,
        ]);

        $priceList->refresh();
        $version->refresh();
        $item->refresh();

        self::assertSame(
            'public_id',
            $priceList->getRouteKeyName(),
        );

        self::assertTrue(
            Str::isUuid(
                (string) $priceList->getAttribute('public_id'),
            ),
        );

        self::assertTrue(
            $priceList->organizationRelationship->is(
                $relationship,
            ),
        );

        self::assertTrue(
            $priceList->ownerOrganization->is(
                $customer,
            ),
        );

        self::assertTrue(
            $priceList->customerOrganization->is(
                $customer,
            ),
        );

        self::assertTrue(
            $priceList->providerOrganization->is(
                $provider,
            ),
        );

        self::assertTrue(
            $priceList->createdBy->is($user),
        );

        self::assertTrue(
            $priceList->versions->contains($version),
        );

        self::assertTrue(
            $version->priceList->is($priceList),
        );

        self::assertTrue(
            $version->items->contains($item),
        );

        self::assertTrue(
            $item->priceListVersion->is($version),
        );

        $this->assertDatabaseHas(
            'price_list_items',
            [
                'id' => $item->getKey(),
                'code' => PriceListItem::CODE_ACTUAL_KM,
                'currency' => 'CZK',
                'quantity_source' => PriceListItem::QUANTITY_SOURCE_ACTUAL_KM,
            ],
        );
    }

    public function test_it_applies_defaults_casts_and_status_helpers(): void
    {
        [
            $customer,
            $provider,
            $relationship,
            $user,
        ] = $this->createCommercialParties();

        $priceList = PriceList::query()->create([
            'organization_relationship_id' => $relationship->getKey(),
            'owner_organization_id' => $customer->getKey(),
            'customer_organization_id' => $customer->getKey(),
            'provider_organization_id' => $provider->getKey(),
            'name' => 'Draft partner pricing',
            'currency' => 'CZK',
            'created_by_user_id' => $user->getKey(),
        ]);

        $version = $priceList->versions()->create([
            'version_number' => 1,
            'valid_from' => '2026-08-01',
            'created_by_user_id' => $user->getKey(),
        ]);

        $item = $version->items()->create([
            'code' => PriceListItem::CODE_DELIVERED_PARCELS,
            'description' => 'Delivered parcels',
            'unit' => PriceListItem::UNIT_PARCEL,
            'unit_rate' => '4.2500',
            'currency' => 'CZK',
            'quantity_source' => PriceListItem::QUANTITY_SOURCE_DELIVERED_PARCELS,
            'position' => 1,
        ]);

        $priceList->refresh();
        $version->refresh();
        $item->refresh();

        self::assertSame(
            1,
            $version->getAttribute('lock_version'),
        );

        self::assertTrue($priceList->isDraft());
        self::assertFalse($priceList->isActive());
        self::assertFalse($priceList->isArchived());
        self::assertSame(1, $priceList->getAttribute('current_version'));

        self::assertTrue($version->isDraft());
        self::assertFalse($version->isApproved());
        self::assertFalse($version->isActive());

        self::assertSame(
            '2026-08-01',
            CarbonImmutable::parse(
                (string) $version->valid_from,
            )->format('Y-m-d'),
        );

        self::assertSame(
            PriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE,
            $item->getAttribute('calculation_method'),
        );

        self::assertSame(
            PriceListItem::ROUNDING_METHOD_HALF_UP,
            $item->getAttribute('rounding_method'),
        );

        self::assertSame(
            '4.2500',
            $item->getAttribute('unit_rate'),
        );

        self::assertSame(
            2,
            $item->getAttribute('rounding_scale'),
        );

        self::assertSame(
            1,
            $item->getAttribute('position'),
        );

        self::assertTrue($item->isParcelItem());
        self::assertFalse($item->isKilometreItem());
    }

    public function test_price_list_version_applicability_uses_inclusive_effective_dates(): void
    {
        [
            $customer,
            $provider,
            $relationship,
            $user,
        ] = $this->createCommercialParties();

        $priceList = PriceList::query()->create([
            'organization_relationship_id' => $relationship->getKey(),
            'owner_organization_id' => $customer->getKey(),
            'customer_organization_id' => $customer->getKey(),
            'provider_organization_id' => $provider->getKey(),
            'name' => 'Effective-period pricing',
            'currency' => 'CZK',
            'status' => PriceList::STATUS_ACTIVE,
            'created_by_user_id' => $user->getKey(),
        ]);

        $version = $priceList->versions()->create([
            'version_number' => 1,
            'status' => PriceListVersion::STATUS_ACTIVE,
            'valid_from' => '2026-07-10',
            'valid_until' => '2026-07-20',
            'created_by_user_id' => $user->getKey(),
            'approved_by_user_id' => $user->getKey(),
            'approved_at' => '2026-07-09 10:00:00',
            'activated_at' => '2026-07-10 00:00:00',
        ]);

        self::assertFalse(
            $version->isApplicableOn(
                CarbonImmutable::parse('2026-07-09'),
            ),
        );

        self::assertTrue(
            $version->isApplicableOn(
                CarbonImmutable::parse('2026-07-10'),
            ),
        );

        self::assertTrue(
            $version->isApplicableOn(
                CarbonImmutable::parse('2026-07-20'),
            ),
        );

        self::assertFalse(
            $version->isApplicableOn(
                CarbonImmutable::parse('2026-07-21'),
            ),
        );

        $version->setAttribute(
            'status',
            PriceListVersion::STATUS_EXPIRED,
        );

        self::assertFalse(
            $version->isApplicableOn(
                CarbonImmutable::parse('2026-07-15'),
            ),
        );
    }

    public function test_it_scopes_price_lists_by_owner_and_participating_organization(): void
    {
        [
            $customer,
            $provider,
            $relationship,
            $user,
        ] = $this->createCommercialParties();

        $unrelatedOrganization = $this->createOrganization(
            Organization::TYPE_CARRIER,
        );

        PriceList::query()->create([
            'organization_relationship_id' => $relationship->getKey(),
            'owner_organization_id' => $customer->getKey(),
            'customer_organization_id' => $customer->getKey(),
            'provider_organization_id' => $provider->getKey(),
            'name' => 'Customer-owned pricing',
            'currency' => 'CZK',
            'created_by_user_id' => $user->getKey(),
        ]);

        PriceList::query()->create([
            'organization_relationship_id' => $relationship->getKey(),
            'owner_organization_id' => $provider->getKey(),
            'customer_organization_id' => $customer->getKey(),
            'provider_organization_id' => $provider->getKey(),
            'name' => 'Provider-owned pricing',
            'currency' => 'CZK',
            'created_by_user_id' => $user->getKey(),
        ]);

        self::assertSame(
            1,
            PriceList::query()
                ->forOwnerOrganization(
                    (int) $customer->getKey(),
                )
                ->count(),
        );

        self::assertSame(
            1,
            PriceList::query()
                ->forOwnerOrganization(
                    (int) $provider->getKey(),
                )
                ->count(),
        );

        self::assertSame(
            2,
            PriceList::query()
                ->forParticipatingOrganization(
                    (int) $customer->getKey(),
                )
                ->count(),
        );

        self::assertSame(
            2,
            PriceList::query()
                ->forParticipatingOrganization(
                    (int) $provider->getKey(),
                )
                ->count(),
        );

        self::assertSame(
            0,
            PriceList::query()
                ->forParticipatingOrganization(
                    (int) $unrelatedOrganization->getKey(),
                )
                ->count(),
        );
    }

    /**
     * @return array{
     *     Organization,
     *     Organization,
     *     OrganizationRelationship,
     *     User
     * }
     */
    private function createCommercialParties(): array
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
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);

        $user = User::factory()->create();

        return [
            $customer,
            $provider,
            $relationship,
            $user,
        ];
    }

    private function createOrganization(
        string $type,
    ): Organization {
        return Organization::query()->create([
            'name' => 'Pricing organization '.Str::uuid(),
            'type' => $type,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }
}
