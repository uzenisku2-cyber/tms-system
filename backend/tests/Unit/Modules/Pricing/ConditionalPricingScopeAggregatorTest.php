<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Modules\Pricing\Models\PriceListConditionalRule;
use App\Modules\Pricing\Services\ConditionalPricingScopeAggregator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ConditionalPricingScopeAggregatorTest extends TestCase
{
    public function test_per_route_uses_exactly_one_route(): void
    {
        $result = (new ConditionalPricingScopeAggregator)->aggregate(
            $this->rule('per_route'),
            [
                $this->snapshot(
                    driver: 4,
                    date: '2026-08-01',
                    loaded: 100,
                    redirected: 35,
                ),
            ],
        );

        self::assertSame(
            'per_route',
            $result['evaluation_scope'],
        );

        self::assertSame(
            '2026-08-01',
            $result['period'],
        );

        self::assertSame(
            1,
            $result['route_count'],
        );

        self::assertSame(
            '35.000000',
            $result['metric_numerator_value'],
        );

        self::assertSame(
            '100.000000',
            $result['metric_denominator_value'],
        );

        self::assertSame(
            '35.000000',
            $result['reward_quantity_value'],
        );
    }

    public function test_monthly_driver_aggregates_routes_before_evaluation(): void
    {
        $result = (new ConditionalPricingScopeAggregator)->aggregate(
            $this->rule('monthly_driver'),
            [
                $this->snapshot(
                    driver: 4,
                    date: '2026-08-01',
                    loaded: 100,
                    redirected: 35,
                ),
                $this->snapshot(
                    driver: 4,
                    date: '2026-08-02',
                    loaded: 50,
                    redirected: 5,
                ),
            ],
        );

        self::assertSame(
            'monthly_driver',
            $result['evaluation_scope'],
        );

        self::assertSame(
            4,
            $result['driver_id'],
        );

        self::assertSame(
            '2026-08',
            $result['period'],
        );

        self::assertSame(
            2,
            $result['route_count'],
        );

        self::assertSame(
            '40.000000',
            $result['metric_numerator_value'],
        );

        self::assertSame(
            '150.000000',
            $result['metric_denominator_value'],
        );

        self::assertSame(
            '40.000000',
            $result['reward_quantity_value'],
        );
    }

    public function test_monthly_driver_cannot_mix_drivers(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        (new ConditionalPricingScopeAggregator)->aggregate(
            $this->rule('monthly_driver'),
            [
                $this->snapshot(
                    4,
                    '2026-08-01',
                    100,
                    30,
                ),
                $this->snapshot(
                    5,
                    '2026-08-02',
                    100,
                    30,
                ),
            ],
        );
    }

    public function test_monthly_driver_cannot_mix_months(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        (new ConditionalPricingScopeAggregator)->aggregate(
            $this->rule('monthly_driver'),
            [
                $this->snapshot(
                    4,
                    '2026-08-31',
                    100,
                    30,
                ),
                $this->snapshot(
                    4,
                    '2026-09-01',
                    100,
                    30,
                ),
            ],
        );
    }

    private function rule(
        string $scope,
    ): PriceListConditionalRule {
        $rule = new PriceListConditionalRule;

        $rule->forceFill([
            'evaluation_scope' => $scope,
            'metric_numerator_source' => 'redirected_parcels',
            'metric_denominator_source' => 'loaded_parcels',
            'reward_quantity_source' => 'redirected_parcels',
        ]);

        return $rule;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(
        int $driver,
        string $date,
        int $loaded,
        int $redirected,
    ): array {
        return [
            'performed_by_driver_id' => $driver,
            'service_date' => $date,
            'loaded_parcels' => $loaded,
            'delivered_parcels' => 0,
            'redirected_parcels' => $redirected,
            'undelivered_parcels' => 0,
            'customer_rejected_parcels' => 0,
            'not_delivered_parcels' => $loaded - $redirected,
            'processed_parcels' => $redirected,
            'planned_km' => '0.00',
            'actual_km' => '0.00',
        ];
    }
}
