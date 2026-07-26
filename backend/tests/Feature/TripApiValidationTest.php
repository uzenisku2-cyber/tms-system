<?php

namespace Tests\Feature;


use Tests\TestCase;

use App\Models\User;

use Laravel\Sanctum\Sanctum;

use App\Modules\Trips\Models\Trip;

use Illuminate\Foundation\Testing\RefreshDatabase;



class TripApiValidationTest extends TestCase
{


    use RefreshDatabase;



    public function test_trip_creation_requires_origin(): void
    {


        $user = User::factory()->create();


        Sanctum::actingAs($user);



        $response = $this->postJson(

            '/api/v1/trips',

            [

                'destination' => 'Brno',

            ]

        );



        $response->assertStatus(422);


        $response->assertJsonValidationErrors([

            'origin',

        ]);


    }





    public function test_trip_creation_requires_destination(): void
    {


        $user = User::factory()->create();


        Sanctum::actingAs($user);



        $response = $this->postJson(

            '/api/v1/trips',

            [

                'origin' => 'Praha',

            ]

        );



        $response->assertStatus(422);


        $response->assertJsonValidationErrors([

            'destination',

        ]);


    }





    public function test_trip_creation_rejects_invalid_coordinates(): void
    {


        $user = User::factory()->create();


        Sanctum::actingAs($user);



        $response = $this->postJson(

            '/api/v1/trips',

            [

                'origin' => 'Praha',

                'destination' => 'Brno',

                'origin_lat' => 200,

                'origin_lng' => -300,

                'destination_lat' => 100,

                'destination_lng' => 500,

            ]

        );



        $response->assertStatus(422);


        $response->assertJsonValidationErrors([

            'origin_lat',

            'origin_lng',

            'destination_lat',

            'destination_lng',

        ]);


    }





    public function test_user_can_create_trip(): void
    {


        $user = User::factory()->create();


        Sanctum::actingAs($user);



        $response = $this->postJson(

            '/api/v1/trips',

            [

                'origin' => 'Praha',

                'destination' => 'Brno',

                'origin_lat' => 50.0755,

                'origin_lng' => 14.4378,

                'destination_lat' => 49.1951,

                'destination_lng' => 16.6068,

            ]

        );



        $response->assertStatus(201);


        $this->assertDatabaseHas('trips', [

            'origin' => 'Praha',

            'destination' => 'Brno',

        ]);


    }





    public function test_finished_trip_cannot_change_status(): void
    {


        $user = User::factory()->create();


        Sanctum::actingAs($user);



        $trip = Trip::create([

            'user_id' => $user->id,

            'origin' => 'Praha',

            'destination' => 'Brno',

            'origin_lat' => 50.0755,

            'origin_lng' => 14.4378,

            'destination_lat' => 49.1951,

            'destination_lng' => 16.6068,

            'status' => Trip::STATUS_FINISHED,

        ]);



        $response = $this->patchJson(

            "/api/v1/trips/{$trip->id}",

            [

                'status' => Trip::STATUS_STARTED,

            ]

        );



        $response->assertStatus(422);


    }


}