<?php

namespace Tests\Feature;


use Tests\TestCase;

use App\Models\User;
use App\Models\TripLocation;

use App\Core\Events\EventEnvelope;
use App\Modules\Trips\Domain\Events\TripLocationUpdated;
use App\Modules\Trips\Models\Trip;

use App\Modules\Drivers\Models\Driver;
use App\Modules\Fleet\Models\Vehicle;

use Illuminate\Support\Facades\Cache;

use Illuminate\Foundation\Testing\RefreshDatabase;



class TripRealtimeProjectionTest extends TestCase
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




    public function test_location_event_creates_realtime_cache(): void
    {

        $user = User::factory()->create();



        $trip = $this->createTrip(
            $user
        );



        $location = TripLocation::create([

            'trip_id' => $trip->id,

            'latitude' => 50.087,

            'longitude' => 14.421,

            'speed' => 60,

            'heading' => 180,

        ]);



        $event = EventEnvelope::wrap(

            TripLocationUpdated::fromLocation(
                $location
            ),

            'test-trace'

        );



        event($event);



        $data = Cache::get(

            "trip_live_{$trip->id}"

        );



        $this->assertNotNull(
            $data
        );


        $this->assertSame(
            $trip->id,
            $data['trip_id']
        );


        $this->assertSame(
            50.087,
            $data['latitude']
        );


        $this->assertSame(
            14.421,
            $data['longitude']
        );

    }

}