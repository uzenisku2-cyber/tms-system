<?php

namespace Tests\Feature;


use Tests\TestCase;


use App\Models\User;

use App\Models\TripLocation;


use App\Modules\Trips\Models\Trip;

use App\Modules\Drivers\Models\Driver;

use App\Modules\Fleet\Models\Vehicle;


use Illuminate\Foundation\Testing\RefreshDatabase;

use Laravel\Sanctum\Sanctum;



class TripLifecycleIntegrationTest extends TestCase
{


    use RefreshDatabase;





    public function test_complete_trip_lifecycle(): void

    {


        $user = User::factory()->create();



        Sanctum::actingAs($user);





        $driver = Driver::create([

    'user_id' => $user->id,

    'first_name' => 'Jan',

    'last_name' => 'Novak',

    'license_number' => 'CZ123456789',

]);






        $vehicle = Vehicle::create([

    'user_id' => $user->id,

    'vin' => 'TMB12345678900001',

    'registration_number' => '1AB1234',

    'manufacturer' => 'Skoda',

    'model' => 'Octavia',

    'year' => 2024,

    'active' => true,

]);






        $tripResponse = $this->postJson(

            '/api/v1/trips',

            [

                'origin' => 'Praha',

                'destination' => 'Brno',

                'origin_lat' => 50.0755,

                'origin_lng' => 14.4378,

                'destination_lat' => 49.1951,

                'destination_lng' => 16.6068,

                'scheduled_at' => now()->addHour(),

            ]

        );



        $tripResponse->assertStatus(201);



        $tripId = $tripResponse->json('data.id');



        $trip = Trip::findOrFail($tripId);







        /*
        | Assign driver + vehicle
        */


        $assignResponse = $this->postJson(

            "/api/v1/trips/{$trip->id}/assign",

            [

                'driver_id' => $driver->id,

                'vehicle_id' => $vehicle->id,

            ]

        );



        $assignResponse->assertStatus(200);







        /*
        | Start trip
        */


        $startResponse = $this->postJson(

            "/api/v1/trips/{$trip->id}/start"

        );



        $startResponse->assertStatus(200);



        $this->assertDatabaseHas(

            'trips',

            [

                'id' => $trip->id,

                'status' => Trip::STATUS_STARTED,

            ]

        );







        /*
        | GPS tracking
        */


        $trackingResponse = $this->postJson(

            "/api/v1/trips/{$trip->id}/locations",

            [

                'latitude' => 50.0755,

                'longitude' => 14.4378,

                'speed' => 60,

                'heading' => 90,

            ]

        );



        $trackingResponse->assertStatus(201);





        $this->assertDatabaseHas(

            'trip_locations',

            [

                'trip_id' => $trip->id,

            ]

        );







        /*
        | ETA
        */


        $etaResponse = $this->getJson(

            "/api/v1/trips/{$trip->id}/eta"

        );



        $etaResponse->assertStatus(200);



        $etaResponse->assertJsonPath(

            'source',

            'current_gps'

        );







        /*
        | Live status
        */


        $liveResponse = $this->getJson(

            "/api/v1/trips/{$trip->id}/live"

        );



        $liveResponse->assertStatus(200);



        $liveResponse->assertJsonPath(

            'gps.status',

            'fresh'

        );







        /*
        | Finish trip
        */


        $finishResponse = $this->postJson(

            "/api/v1/trips/{$trip->id}/finish"

        );



        $finishResponse->assertStatus(200);





        $this->assertDatabaseHas(

            'trips',

            [

                'id' => $trip->id,

                'status' => Trip::STATUS_FINISHED,

            ]

        );


    }


}