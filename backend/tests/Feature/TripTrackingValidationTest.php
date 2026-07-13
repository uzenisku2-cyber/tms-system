<?php

namespace Tests\Feature;

use Tests\TestCase;

use App\Models\User;

use App\Modules\Trips\Models\Trip;

use Laravel\Sanctum\Sanctum;

use Illuminate\Foundation\Testing\RefreshDatabase;


class TripTrackingValidationTest extends TestCase
{

    use RefreshDatabase;



    protected function createStartedTrip(
        User $user
    ): Trip
    {

        return Trip::create([

            'user_id' => $user->id,

            'origin' => 'Praha',

            'destination' => 'Brno',

            'origin_lat' => 50.0755,

            'origin_lng' => 14.4378,

            'destination_lat' => 49.1951,

            'destination_lng' => 16.6068,

            'status' => Trip::STATUS_STARTED,

        ]);

    }





    public function test_tracking_requires_latitude(): void
    {

        $user = User::factory()->create();


        Sanctum::actingAs(
            $user
        );


        $trip = $this->createStartedTrip(
            $user
        );


        $response = $this->postJson(

            "/api/v1/trips/{$trip->id}/locations",

            [

                'longitude' => 14.4378,

                'speed' => 50,

            ]

        );




        $response->assertStatus(422);


        $response->assertJsonValidationErrors([

            'latitude',

        ]);

    }





    public function test_tracking_requires_longitude(): void
    {

        $user = User::factory()->create();


        Sanctum::actingAs(
            $user
        );


        $trip = $this->createStartedTrip(
            $user
        );


        $response = $this->postJson(

            "/api/v1/trips/{$trip->id}/locations",

            [

                'latitude' => 50.0755,

                'speed' => 50,

            ]

        );




        $response->assertStatus(422);


        $response->assertJsonValidationErrors([

            'longitude',

        ]);

    }





    public function test_tracking_rejects_invalid_coordinates(): void
    {

        $user = User::factory()->create();


        Sanctum::actingAs(
            $user
        );


        $trip = $this->createStartedTrip(
            $user
        );


        $response = $this->postJson(

            "/api/v1/trips/{$trip->id}/locations",

            [

                'latitude' => 200,

                'longitude' => -300,

                'speed' => 50,

            ]

        );




        $response->assertStatus(422);


        $response->assertJsonValidationErrors([

            'latitude',

            'longitude',

        ]);

    }





    public function test_tracking_rejects_negative_speed(): void
    {

        $user = User::factory()->create();


        Sanctum::actingAs(
            $user
        );


        $trip = $this->createStartedTrip(
            $user
        );


        $response = $this->postJson(

            "/api/v1/trips/{$trip->id}/locations",

            [

                'latitude' => 50.0755,

                'longitude' => 14.4378,

                'speed' => -10,

            ]

        );




        $response->assertStatus(422);


        $response->assertJsonValidationErrors([

            'speed',

        ]);

    }


}