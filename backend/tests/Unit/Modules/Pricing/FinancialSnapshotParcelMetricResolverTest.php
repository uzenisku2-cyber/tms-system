<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Modules\Pricing\Services\FinancialSnapshotParcelMetricResolver;
use DomainException;
use PHPUnit\Framework\TestCase;

final class FinancialSnapshotParcelMetricResolverTest extends TestCase
{
    public function test_business_semantics_are_explicit(): void
    {
        $result = (new FinancialSnapshotParcelMetricResolver)->resolve([
            'loaded_parcels' => 100,
            'delivered_parcels' => 70,
            'redirected_parcels' => 20,
            'undelivered_parcels' => 3,
        ]);

        self::assertSame(3, $result['customer_rejected_parcels']);
        self::assertSame(93, $result['processed_parcels']);
        self::assertSame(7, $result['not_delivered_parcels']);
        self::assertSame(3, $result['undelivered_parcels']);
    }

    public function test_zero_route_is_valid(): void
    {
        $result = (new FinancialSnapshotParcelMetricResolver)->resolve([
            'loaded_parcels' => 0,
            'delivered_parcels' => 0,
            'redirected_parcels' => 0,
            'undelivered_parcels' => 0,
        ]);

        self::assertSame(0, $result['processed_parcels']);
        self::assertSame(0, $result['not_delivered_parcels']);
    }

    public function test_invalid_balance_is_blocked(): void
    {
        $this->expectException(DomainException::class);

        (new FinancialSnapshotParcelMetricResolver)->resolve([
            'loaded_parcels' => 90,
            'delivered_parcels' => 70,
            'redirected_parcels' => 20,
            'undelivered_parcels' => 3,
        ]);
    }
}
