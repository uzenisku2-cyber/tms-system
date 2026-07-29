<?php

namespace App\Modules\DailyReports\Services;

use InvalidArgumentException;

final class RouteNumberNormalizer
{
    /**
     * @return array{
     *     route_number: string,
     *     route_number_normalized: string
     * }
     */
    public function normalize(string $routeNumber): array
    {
        $trimmedRouteNumber = trim($routeNumber);

        if ($trimmedRouteNumber === '') {
            throw new InvalidArgumentException(
                'Route number must not be empty.',
            );
        }

        return [
            'route_number' => $trimmedRouteNumber,
            'route_number_normalized' => mb_strtolower(
                $trimmedRouteNumber,
                'UTF-8',
            ),
        ];
    }
}
