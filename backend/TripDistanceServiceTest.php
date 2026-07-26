<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\TripDistanceService;
use App\Modules\Trips\Models\Trip;


class TripDistanceServiceTest extends TestCase
{

    public function test_returns_stored_distance(): void
    {

        $trip = new Trip();

        $trip->distance_km = 123;


        $service = app(
            TripDistanceService::class
        );


        $this->assertSame(
            123.0,
            $service->calculate($trip)
        );

    }

}
