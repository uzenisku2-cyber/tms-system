<?php

namespace Tests\Feature;


use Tests\TestCase;


use App\Models\User;

use App\Modules\Drivers\Models\Driver;

use App\Modules\Trips\Models\Trip;


use Illuminate\Foundation\Testing\RefreshDatabase;

use Laravel\Sanctum\Sanctum;



class TripPermissionTest extends TestCase
{


    use RefreshDatabase;





    protected function createDriver(
        User $user
    ): Driver {


        return Driver::create([

            'user_id' => $user->id,

            'first_name' => 'Driver',

            'last_name' => 'Test',

            'license_number' => 'LIC-' . $user->id,

        ]);

    }





    protected function createTrip(
    Driver $driver
): Trip {

    return Trip::create([

        'user_id' => $driver->user_id,

        'driver_id' => $driver->id,

        'origin' => 'Praha',

        'destination' => 'Brno',

        'origin_lat' => 50.0755,

        'origin_lng' => 14.4378,

        'destination_lat' => 49.1951,

        'destination_lng' => 16.6068,

        'status' => Trip::STATUS_ASSIGNED,

    ]);



    }





    public function test_driver_cannot_start_other_driver_trip(): void
    {


        $owner = User::factory()->create();

        $otherUser = User::factory()->create();



        $ownerDriver = $this->createDriver($owner);

        $this->createDriver($otherUser);



        $trip = $this->createTrip($ownerDriver);



        Sanctum::actingAs($otherUser);



        $response = $this->postJson(

            "/api/v1/trips/{$trip->id}/start"

        );



        $response->assertStatus(403);


    }







    public function test_driver_cannot_finish_other_driver_trip(): void
    {


        $owner = User::factory()->create();

        $otherUser = User::factory()->create();



        $ownerDriver = $this->createDriver($owner);

        $this->createDriver($otherUser);



        $trip = $this->createTrip($ownerDriver);



        $trip->update([

            'status' => Trip::STATUS_STARTED,

        ]);



        Sanctum::actingAs($otherUser);



        $response = $this->postJson(

            "/api/v1/trips/{$trip->id}/finish"

        );



        $response->assertStatus(403);


    }







    public function test_driver_can_start_own_trip(): void
    {


        $user = User::factory()->create();



        $driver = $this->createDriver($user);



        $trip = $this->createTrip($driver);



        Sanctum::actingAs($user);



        $response = $this->postJson(

            "/api/v1/trips/{$trip->id}/start"

        );



        $response->assertStatus(200);



        $this->assertDatabaseHas(

            'trips',

            [

                'id' => $trip->id,

                'status' => Trip::STATUS_STARTED,

            ]

        );


    }


}