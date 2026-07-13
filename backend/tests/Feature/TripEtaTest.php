<?php

namespace Tests\Feature;


use Tests\TestCase;


use App\Models\User;

use App\Models\TripLocation;


use App\Modules\Trips\Models\Trip;


use Illuminate\Foundation\Testing\RefreshDatabase;

use Laravel\Sanctum\Sanctum;



class TripEtaTest extends TestCase
{


    use RefreshDatabase;





    protected function createTrip(

        User $user,

        array $attributes = []

    ): Trip {


        return Trip::create(array_merge([


            'user_id' => $user->id,


            'origin' => 'Praha',


            'destination' => 'Brno',


            'origin_lat' => 50.0755,


            'origin_lng' => 14.4378,


            'destination_lat' => 49.1951,


            'destination_lng' => 16.6068,


            'distance_km' => 140,


            'status' => Trip::STATUS_STARTED,


        ], $attributes));


    }







    public function test_started_trip_returns_eta(): void

    {


        $user = User::factory()->create();


        Sanctum::actingAs($user);



        $trip = $this->createTrip($user);



        $response = $this->getJson(

            "/api/v1/trips/{$trip->id}/eta"

        );



        $response->assertStatus(200);



        $response->assertJsonStructure([

            'trip_id',

            'status',

            'distance_km',

            'source',

            'average_speed_kmh',

            'estimated_minutes',

            'arrival_time',

        ]);



        $response->assertJsonPath(

            'average_speed_kmh',

            70

        );


        $response->assertJsonPath(

            'source',

            'planned_route'

        );


    }








    public function test_eta_uses_current_gps_position(): void

    {


        $user = User::factory()->create();


        Sanctum::actingAs($user);



        $trip = $this->createTrip($user);



        TripLocation::create([

            'trip_id' => $trip->id,

            'latitude' => 50.0755,

            'longitude' => 14.4378,

            'speed' => 50,

        ]);



        $response = $this->getJson(

            "/api/v1/trips/{$trip->id}/eta"

        );



        $response->assertStatus(200);



        $response->assertJsonPath(

            'source',

            'current_gps'

        );



        $response->assertJsonPath(

            'average_speed_kmh',

            70

        );


    }








    public function test_finished_trip_returns_zero_eta(): void

    {


        $user = User::factory()->create();


        Sanctum::actingAs($user);



        $trip = $this->createTrip(

            $user,

            [

                'status' => Trip::STATUS_FINISHED,

                'finished_at' => now(),

            ]

        );



        $response = $this->getJson(

            "/api/v1/trips/{$trip->id}/eta"

        );



        $response->assertStatus(200);



        $response->assertJson([

            'status' => Trip::STATUS_FINISHED,

            'distance_km' => 0,

            'estimated_minutes' => 0,

        ]);

    }








    public function test_cancelled_trip_returns_empty_eta(): void

    {


        $user = User::factory()->create();


        Sanctum::actingAs($user);



        $trip = $this->createTrip(

            $user,

            [

                'status' => Trip::STATUS_CANCELLED,

            ]

        );



        $response = $this->getJson(

            "/api/v1/trips/{$trip->id}/eta"

        );



        $response->assertStatus(200);



        $response->assertJson([

            'status' => Trip::STATUS_CANCELLED,

            'distance_km' => null,

            'estimated_minutes' => null,

            'arrival_time' => null,

        ]);

    }


}