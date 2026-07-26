<?php

namespace Tests\Feature;


use Tests\TestCase;


use App\Models\User;


use App\Modules\Trips\Models\Trip;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Fleet\Models\Vehicle;


use Laravel\Sanctum\Sanctum;


use Illuminate\Foundation\Testing\RefreshDatabase;



class TripEtaTest extends TestCase
{


    use RefreshDatabase;





    protected function authenticate(): User
    {


        $user = User::factory()->create();


        Sanctum::actingAs(

            $user

        );


        return $user;


    }









    protected function createTrip(

        User $user

    ): Trip
    {


        $driver = Driver::create([


            'user_id' => $user->id,


            'first_name' => 'Jan',


            'last_name' => 'Novak',


            'license_number' => 'CZ' . uniqid(),


            'active' => true,


        ]);





        $vehicle = Vehicle::create([


            'user_id' => $user->id,


            'registration_number' =>

                '1AB' . strtoupper(substr(uniqid(), -5)),


            'manufacturer' => 'Skoda',


            'model' => 'Octavia',


            'vin' => strtoupper(substr(uniqid('VIN'), 0, 17)),


            'year' => 2024,


            'active' => true,


        ]);





        return Trip::create([


            'user_id' => $user->id,


            'driver_id' => $driver->id,


            'vehicle_id' => $vehicle->id,


            'origin' => 'Praha',


            'destination' => 'Brno',


            'origin_lat' => 50.0755,


            'origin_lng' => 14.4378,


            'destination_lat' => 49.1951,


            'destination_lng' => 16.6068,


            'distance_km' => 200,


            'status' => Trip::STATUS_STARTED,


        ]);


    }









    public function test_trip_eta_returns_estimation(): void
    {


        $user = $this->authenticate();




        $trip = $this->createTrip(

            $user

        );





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

            'source',

            'planned_route'

        );





        $response->assertJsonPath(

            'average_speed_kmh',

            70

        );


    }


}