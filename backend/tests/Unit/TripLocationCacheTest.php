<?php

namespace Tests\Unit;

use Tests\TestCase;

use App\Models\User;
use App\Models\TripLocation;

use App\Modules\Trips\Models\Trip;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;


class TripLocationCacheTest extends TestCase
{

    use RefreshDatabase;



    public function test_trip_location_update_clears_distance_cache(): void
    {


        $user = User::factory()->create();



        $trip = Trip::create([

            'user_id' => $user->id,

            'origin' => 'Praha',

            'destination' => 'Brno',

            'status' => Trip::STATUS_STARTED,

        ]);



        TripLocation::create([

            'trip_id' => $trip->id,

            'latitude' => 50.0755,

            'longitude' => 14.4378,

        ]);



        TripLocation::create([

            'trip_id' => $trip->id,

            'latitude' => 49.1951,

            'longitude' => 16.6068,

        ]);



        $service = app(
            \App\Services\TripDistanceService::class
        );



        $service->calculate(
            $trip
        );



        $this->assertTrue(
            Cache::has(
                "trip_distance_{$trip->id}"
            )
        );



        $location = TripLocation::first();



        $location->latitude = 51.0000;

        $location->save();



        $this->assertFalse(
            Cache::has(
                "trip_distance_{$trip->id}"
            )
        );


    }





    public function test_trip_location_creation_clears_distance_cache(): void
    {


        $user = User::factory()->create();



        $trip = Trip::create([

            'user_id' => $user->id,

            'origin' => 'Praha',

            'destination' => 'Brno',

            'status' => Trip::STATUS_STARTED,

        ]);



        Cache::put(

            "trip_distance_{$trip->id}",

            999

        );



        $this->assertTrue(
            Cache::has(
                "trip_distance_{$trip->id}"
            )
        );



        TripLocation::create([

            'trip_id' => $trip->id,

            'latitude' => 50.0755,

            'longitude' => 14.4378,

        ]);



        $this->assertFalse(
            Cache::has(
                "trip_distance_{$trip->id}"
            )
        );


    }





    public function test_trip_location_deletion_clears_distance_cache(): void
    {


        $user = User::factory()->create();



        $trip = Trip::create([

            'user_id' => $user->id,

            'origin' => 'Praha',

            'destination' => 'Brno',

            'status' => Trip::STATUS_STARTED,

        ]);



        $location = TripLocation::create([

            'trip_id' => $trip->id,

            'latitude' => 50.0755,

            'longitude' => 14.4378,

        ]);



        Cache::put(

            "trip_distance_{$trip->id}",

            999

        );



        $this->assertTrue(
            Cache::has(
                "trip_distance_{$trip->id}"
            )
        );



        $location->delete();



        $this->assertFalse(
            Cache::has(
                "trip_distance_{$trip->id}"
            )
        );


    }


}