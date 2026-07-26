<?php

namespace Tests\Feature;


use Tests\TestCase;

use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\User;

use Laravel\Sanctum\Sanctum;



class TripSecurityTest extends TestCase
{


    use RefreshDatabase;





    public function test_guest_cannot_access_trip_live(): void
    {


        $response = $this->getJson(

            '/api/v1/trips/1/live'

        );


        $response->assertStatus(401);


    }







    public function test_guest_cannot_access_trip_eta(): void
    {


        $response = $this->getJson(

            '/api/v1/trips/1/eta'

        );


        $response->assertStatus(401);


    }







    public function test_guest_cannot_send_tracking_location(): void
    {


        $response = $this->postJson(

            '/api/v1/trips/1/locations',

            [

                'latitude' => 50.0755,

                'longitude' => 14.4378,

                'speed' => 50,

            ]

        );


        $response->assertStatus(401);


    }







    public function test_guest_cannot_access_alerts(): void
    {


        $response = $this->getJson(

            '/api/v1/alerts'

        );


        $response->assertStatus(401);


    }







    public function test_authenticated_user_can_access_protected_route(): void
    {


        $user = User::factory()->create();



        Sanctum::actingAs($user);



        $response = $this->getJson(

            '/api/v1/alerts'

        );


        $response->assertStatus(200);


    }


}