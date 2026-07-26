<?php

namespace Tests\Feature;

use Tests\TestCase;

use App\Models\User;
use App\Models\TripLocation;

use App\Modules\Trips\Models\Trip;

use Laravel\Sanctum\Sanctum;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;


class TripTrackingTest extends TestCase
{

    use RefreshDatabase;



    public function test_trip_location_can_be_created_from_tracking_endpoint(): void
    {


        $user = User::factory()->create();



        Sanctum::actingAs(
            $user
        );



        $trip = Trip::create([

            'user_id' => $user->id,

            'origin' => 'Praha',

            'destination' => 'Brno',

            'status' => Trip::STATUS_STARTED,

        ]);



        $response = $this->postJson(

            "/api/v1/trips/{$trip->id}/locations",

            [

                'latitude' => 50.0755,

                'longitude' => 14.4378,

                'speed' => 60,

            ]

        );



        $response->assertStatus(201);



        $this->assertDatabaseHas(

            'trip_locations',

            [

                'trip_id' => $trip->id,

                'latitude' => 50.0755,

                'longitude' => 14.4378,

            ]

        );


    }





    public function test_trip_location_creation_clears_distance_cache(): void
    {


        $user = User::factory()->create();



        Sanctum::actingAs(
            $user
        );



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



        $response = $this->postJson(

            "/api/v1/trips/{$trip->id}/locations",

            [

                'latitude' => 50.0755,

                'longitude' => 14.4378,

                'speed' => 55,

            ]

        );



        $response->assertStatus(201);



        $this->assertFalse(

            Cache::has(

                "trip_distance_{$trip->id}"

            )

        );


    }


}