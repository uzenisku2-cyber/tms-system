<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Modules\Pricing\Services\ProvisionalRouteRemunerationCalculator;
use PHPUnit\Framework\TestCase;

final class ProvisionalRouteRemunerationCalculatorTest extends TestCase
{
    public function test_quality_at_twenty_percent_and_independent_manual_surcharge(): void
    {
        $result = (new ProvisionalRouteRemunerationCalculator)->calculate(
            ['delivered' => 10, 'redirected' => 2, 'km' => '31.50', 'surcharge' => '150.00'],
            ['delivered' => '33.0000', 'redirected' => '15.0000', 'km' => '4.0000', 'quality' => '4.0000'],
            ['loaded' => 100, 'delivered' => 15, 'redirected' => 3, 'rejected' => 2],
        );

        self::assertSame(33000, $result['delivered_minor']);
        self::assertSame(3000, $result['redirected_minor']);
        self::assertSame(12600, $result['km_minor']);
        self::assertSame(4800, $result['quality_minor']);
        self::assertSame(15000, $result['surcharge_minor']);
        self::assertSame(68400, $result['total_minor']);
        self::assertTrue($result['quality_eligible']);
    }

    public function test_quality_below_threshold_does_not_remove_manual_surcharge(): void
    {
        $result = (new ProvisionalRouteRemunerationCalculator)->calculate(
            ['delivered' => 1, 'redirected' => 0, 'km' => '0', 'surcharge' => '7.25'],
            ['delivered' => '34', 'redirected' => '15', 'km' => '4', 'quality' => '4'],
            ['loaded' => 100, 'delivered' => 19, 'redirected' => 0, 'rejected' => 0],
        );

        self::assertSame(0, $result['quality_minor']);
        self::assertSame(725, $result['surcharge_minor']);
        self::assertSame(4125, $result['total_minor']);
        self::assertFalse($result['quality_eligible']);
    }

    public function test_decimal_rates_are_rounded_after_multiplication(): void
    {
        $result = (new ProvisionalRouteRemunerationCalculator)->calculate(
            ['delivered' => 2, 'redirected' => 0, 'km' => '0.25', 'surcharge' => '0'],
            ['delivered' => '1.2345', 'redirected' => '0', 'km' => '4.0000', 'quality' => '0'],
            ['loaded' => 0, 'delivered' => 0, 'redirected' => 0, 'rejected' => 0],
        );

        self::assertSame(247, $result['delivered_minor']);
        self::assertSame(100, $result['km_minor']);
        self::assertSame(347, $result['total_minor']);
    }
}
