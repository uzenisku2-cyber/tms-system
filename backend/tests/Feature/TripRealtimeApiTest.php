<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

use App\Models\User;
use App\Models\TripLocation;

use App\Modules\Trips\Models\Trip;

use App\Modules\Drivers\Models\Driver;
use App\Modules\Fleet\Models\Vehicle;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

use Laravel\Sanctum\Sanctum;


class TripRealtimeApiTest extends TestCase
{

    use RefreshDatabase;



    protected function createTrip(
        User $user
    ): Trip {


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

            'origin' => 'Prague',

            'destination' => 'Brno',

            'status' => Trip::STATUS_PLANNED,

        ]);

    }




    public function test_realtime_endpoint_returns_cached_position(): void
    {

        $user = User::factory()->create();


        Sanctum::actingAs(
            $user
        );


        $trip = $this->createTrip(
            $user
        );



        Cache::put(

            "trip_live_{$trip->id}",

            [

                'trip_id' => $trip->id,

                'latitude' => 50.087,

                'longitude' => 14.421,

                'speed' => 60,

                'heading' => 180,

                'updated_at' => now(),

            ]

        );



        $response = $this->getJson(

            "/api/v1/trips/{$trip->id}/realtime"

        );



        $response->assertStatus(200);



        $response->assertJson([

            'trip_id' => $trip->id,

            'realtime' => [

                'latitude' => 50.087,

                'longitude' => 14.421,

                'speed' => 60,

                'heading' => 180,

            ],

        ]);

    }




    public function test_realtime_endpoint_returns_null_without_state(): void
    {

        $user = User::factory()->create();


        Sanctum::actingAs(
            $user
        );


        $trip = $this->createTrip(
            $user
        );



        Cache::forget(

            "trip_live_{$trip->id}"

        );



        $response = $this->getJson(

            "/api/v1/trips/{$trip->id}/realtime"

        );



        $response->assertStatus(200);



        $response->assertJson([

            'trip_id' => $trip->id,

            'realtime' => null,

        ]);

    }


}