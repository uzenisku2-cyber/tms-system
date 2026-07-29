<?php

namespace Tests\Unit\Modules\DailyReports;

use App\Modules\DailyReports\Services\DailyReportCalculations;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DailyReportCalculationsTest extends TestCase
{
    public function test_it_calculates_total_processed_parcels(): void
    {
        $calculations = new DailyReportCalculations;

        self::assertSame(
            125,
            $calculations->totalProcessedParcels(
                100,
                15,
                10,
            ),
        );
    }

    public function test_it_rejects_negative_parcel_counts(): void
    {
        $calculations = new DailyReportCalculations;

        $this->expectException(
            InvalidArgumentException::class,
        );

        $calculations->totalProcessedParcels(
            100,
            -1,
            10,
        );
    }

    public function test_it_calculates_positive_kilometre_difference(): void
    {
        $calculations = new DailyReportCalculations;

        self::assertEqualsWithDelta(
            12.50,
            $calculations->differenceKm(
                100.00,
                112.50,
            ),
            0.0001,
        );
    }

    public function test_it_calculates_negative_kilometre_difference(): void
    {
        $calculations = new DailyReportCalculations;

        self::assertEqualsWithDelta(
            -7.25,
            $calculations->differenceKm(
                125.50,
                118.25,
            ),
            0.0001,
        );
    }

    public function test_it_calculates_deviation_percentage(): void
    {
        $calculations = new DailyReportCalculations;

        self::assertEqualsWithDelta(
            12.50,
            $calculations->deviationPercentage(
                100.00,
                112.50,
            ),
            0.0001,
        );
    }

    public function test_only_deviation_above_ten_percent_requires_attention(): void
    {
        $calculations = new DailyReportCalculations;

        self::assertFalse(
            $calculations->requiresKilometreAttention(
                100.00,
                109.99,
            ),
        );

        self::assertFalse(
            $calculations->requiresKilometreAttention(
                100.00,
                110.00,
            ),
        );

        self::assertTrue(
            $calculations->requiresKilometreAttention(
                100.00,
                110.01,
            ),
        );
    }

    public function test_zero_planned_kilometres_require_attention(): void
    {
        $calculations = new DailyReportCalculations;

        self::assertNull(
            $calculations->deviationPercentage(
                0.00,
                25.00,
            ),
        );

        self::assertTrue(
            $calculations->requiresKilometreAttention(
                0.00,
                25.00,
            ),
        );
    }

    public function test_it_rejects_negative_kilometres(): void
    {
        $calculations = new DailyReportCalculations;

        $this->expectException(
            InvalidArgumentException::class,
        );

        $calculations->differenceKm(
            -1.00,
            10.00,
        );
    }

    public function test_it_rejects_non_finite_kilometres(): void
    {
        $calculations = new DailyReportCalculations;

        $this->expectException(
            InvalidArgumentException::class,
        );

        $calculations->differenceKm(
            10.00,
            INF,
        );
    }
}
