<?php

namespace Tests\Feature;


use Tests\TestCase;


use App\Models\User;

use App\Models\Alert;

use App\Models\TripLocation;


use App\Modules\Trips\Models\Trip;

use App\Modules\Drivers\Models\Driver;

use App\Modules\Fleet\Models\Vehicle;


use Illuminate\Foundation\Testing\RefreshDatabase;

use Laravel\Sanctum\Sanctum;



class TripLiveTest extends TestCase
{


    use RefreshDatabase;




    protected function createTrip(
        User $user
    ): Trip {


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






    public function test_live_trip_returns_basic_structure(): void

    {


        $user = User::factory()->create();


        Sanctum::actingAs($user);



        $trip = $this->createTrip($user);



        $response = $this->getJson(

            "/api/v1/trips/{$trip->id}/live"

        );



        $response->assertStatus(200);



        $response->assertJsonStructure([

            'trip_id',

            'status',

            'driver',

            'vehicle',

            'location',

            'gps' => [

                'age_seconds',

                'status',

                'attention',

            ],

            'alerts',

        ]);

    }







    public function test_live_trip_returns_fresh_gps(): void

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

            "/api/v1/trips/{$trip->id}/live"

        );



        $response->assertStatus(200);



        $response->assertJsonPath(

            'gps.status',

            'fresh'

        );



        $response->assertJsonPath(

            'gps.attention',

            false

        );


    }







    public function test_live_trip_returns_lost_gps(): void

    {


        $user = User::factory()->create();


        Sanctum::actingAs($user);



        $trip = $this->createTrip($user);



        $location = TripLocation::create([

            'trip_id' => $trip->id,

            'latitude' => 50.0755,

            'longitude' => 14.4378,

            'speed' => 0,

        ]);



        $location->created_at = now()->subMinutes(10);

        $location->updated_at = now()->subMinutes(10);

        $location->save();



        $response = $this->getJson(

            "/api/v1/trips/{$trip->id}/live"

        );



        $response->assertStatus(200);



        $response->assertJsonPath(

            'gps.status',

            'lost'

        );



        $response->assertJsonPath(

            'gps.attention',

            true

        );


    }







    public function test_live_trip_returns_active_alerts(): void

    {


        $user = User::factory()->create();


        Sanctum::actingAs($user);



        $trip = $this->createTrip($user);



        Alert::create([

            'trip_id' => $trip->id,

            'user_id' => $user->id,

            'type' => 'gps_lost',

            'severity' => 'warning',

            'message' => 'GPS signal lost',

        ]);



        $response = $this->getJson(

            "/api/v1/trips/{$trip->id}/live"

        );



        $response->assertStatus(200);



        $response->assertJsonCount(

            1,

            'alerts'

        );


    }



}