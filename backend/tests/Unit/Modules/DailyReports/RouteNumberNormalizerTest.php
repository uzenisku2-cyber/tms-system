<?php

namespace Tests\Unit\Modules\DailyReports;

use App\Modules\DailyReports\Services\RouteNumberNormalizer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RouteNumberNormalizerTest extends TestCase
{
    public function test_it_trims_and_normalizes_route_number(): void
    {
        $normalizer = new RouteNumberNormalizer;

        self::assertSame(
            [
                'route_number' => 'ROUTE-01',
                'route_number_normalized' => 'route-01',
            ],
            $normalizer->normalize(
                '  ROUTE-01  ',
            ),
        );
    }

    public function test_it_normalizes_utf8_characters(): void
    {
        $normalizer = new RouteNumberNormalizer;

        self::assertSame(
            [
                'route_number' => 'ČÁSLAV-12',
                'route_number_normalized' => 'čáslav-12',
            ],
            $normalizer->normalize(
                ' ČÁSLAV-12 ',
            ),
        );
    }

    public function test_it_preserves_internal_spacing(): void
    {
        $normalizer = new RouteNumberNormalizer;

        self::assertSame(
            [
                'route_number' => 'A  12',
                'route_number_normalized' => 'a  12',
            ],
            $normalizer->normalize(
                '  A  12  ',
            ),
        );
    }

    public function test_it_rejects_empty_route_number(): void
    {
        $normalizer = new RouteNumberNormalizer;

        $this->expectException(
            InvalidArgumentException::class,
        );

        $normalizer->normalize(" \t\n ");
    }
}
