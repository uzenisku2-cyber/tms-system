<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\TripDistanceService;
use App\Modules\Trips\Models\Trip;
use Illuminate\Support\Facades\Cache;


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





    public function test_returns_city_fallback_distance(): void
    {

        $trip = new Trip();

        $trip->origin = 'Praha';

        $trip->destination = 'Brno';


        $trip->setRelation(
            'locations',
            collect()
        );


        $service = app(
            TripDistanceService::class
        );


        $this->assertSame(
            185.0,
            $service->calculate($trip)
        );

    }





    public function test_calculates_distance_from_gps_locations(): void
    {

        $trip = new Trip();


        $trip->setRelation(
            'locations',
            collect([

                (object) [

                    'latitude' => 50.0755,

                    'longitude' => 14.4378,

                    'created_at' => now(),

                ],


                (object) [

                    'latitude' => 49.1951,

                    'longitude' => 16.6068,

                    'created_at' => now()->addMinute(),

                ],

            ])
        );



        $service = app(
            TripDistanceService::class
        );



        $distance = $service->calculate(
            $trip
        );



        $this->assertGreaterThan(
            100,
            $distance
        );


        $this->assertLessThan(
            200,
            $distance
        );

    }





    public function test_stores_calculated_distance_in_cache(): void
    {

        Cache::flush();


        $trip = new Trip();

        $trip->id = 7;

        $trip->origin = 'Praha';

        $trip->destination = 'Brno';


        $trip->setRelation(
            'locations',
            collect()
        );


        $service = app(
            TripDistanceService::class
        );


        $service->calculate(
            $trip
        );


        $this->assertTrue(
            Cache::has(
                'trip_distance_7'
            )
        );

    }


}