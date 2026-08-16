<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Modules\Pricing\Models\DriverPriceList;
use App\Modules\Pricing\Models\DriverPriceListItem;
use App\Modules\Pricing\Models\DriverPriceListVersion;
use App\Modules\Pricing\Models\PriceListItem;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class DriverPriceListFoundationContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_price_list_is_a_dedicated_assignment_scoped_aggregate(): void
    {
        $this->assertTrue(
            Schema::hasColumns('driver_price_lists', [
                'public_id',
                'driver_organization_assignment_id',
                'managed_by_organization_id',
                'code',
                'name',
                'currency',
                'status',
                'current_version',
                'created_by_user_id',
            ]),
        );

        $this->assertFalse(
            Schema::hasColumn(
                'driver_price_lists',
                'organization_relationship_id',
            ),
        );

        $this->assertFalse(
            Schema::hasColumn(
                'driver_price_lists',
                'price_list_id',
            ),
        );
    }

    public function test_driver_price_list_has_independent_version_and_item_storage(): void
    {
        $this->assertTrue(
            Schema::hasColumns('driver_price_list_versions', [
                'driver_price_list_id',
                'version_number',
                'lock_version',
                'status',
                'valid_from',
                'valid_until',
                'created_by_user_id',
                'approved_by_user_id',
                'approved_at',
                'activated_at',
            ]),
        );

        $this->assertTrue(
            Schema::hasColumns('driver_price_list_items', [
                'driver_price_list_version_id',
                'code',
                'unit',
                'unit_rate',
                'currency',
                'quantity_source',
                'position',
            ]),
        );
    }

    public function test_driver_pricing_uses_the_existing_canonical_metric_taxonomy(): void
    {
        $this->assertSame(
            PriceListItem::CODES,
            DriverPriceListItem::CODES,
        );

        $this->assertSame(
            [
                'delivered_parcels',
                'redirected_parcels',
                'undelivered_parcels',
                'actual_km',
            ],
            DriverPriceListItem::CODES,
        );
    }

    public function test_driver_price_list_has_its_own_lifecycle(): void
    {
        $this->assertSame(
            ['draft', 'active', 'archived'],
            DriverPriceList::STATUSES,
        );

        $this->assertSame(
            [
                'draft',
                'approved',
                'active',
                'replaced',
                'expired',
            ],
            DriverPriceListVersion::STATUSES,
        );
    }

    public function test_driver_price_list_version_applicability_is_effective_dated(): void
    {
        $version = new DriverPriceListVersion([
            'status' => DriverPriceListVersion::STATUS_ACTIVE,
            'valid_from' => '2026-08-01',
            'valid_until' => '2026-08-31',
        ]);

        $this->assertTrue(
            $version->isApplicableOn(
                CarbonImmutable::parse('2026-08-15'),
            ),
        );

        $this->assertFalse(
            $version->isApplicableOn(
                CarbonImmutable::parse('2026-09-01'),
            ),
        );
    }
}
