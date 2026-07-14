<?php

namespace Tests\Feature;


use Tests\TestCase;


use App\Models\User;
use App\Models\Alert;


use App\Services\TripMonitoringService;


use App\Modules\Trips\Models\Trip;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Fleet\Models\Vehicle;


use Laravel\Sanctum\Sanctum;


use Illuminate\Support\Facades\Notification;


use Illuminate\Foundation\Testing\RefreshDatabase;



class TripMonitoringTest extends TestCase
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









    public function test_creates_gps_lost_alert(): void
    {


        Notification::fake();



        $user = $this->authenticate();



        $trip = $this->createTrip(

            $user

        );





        $service = app(

            TripMonitoringService::class

        );





        $alert = $service->checkGpsLost(

            $trip

        );





        $this->assertNotNull(

            $alert

        );





        $this->assertEquals(

            'gps_lost',

            $alert->type

        );





        $this->assertDatabaseHas(

            'alerts',

            [

                'trip_id' => $trip->id,

                'type' => 'gps_lost',

            ]

        );


    }









    public function test_creates_eta_delay_alert(): void
    {


        Notification::fake();



        $user = $this->authenticate();



        $trip = $this->createTrip(

            $user

        );





        $trip->update([


            'scheduled_at' => now()->subMinutes(10),


        ]);





        $service = app(

            TripMonitoringService::class

        );





        $alert = $service->checkEtaDelay(

            $trip->fresh()

        );





        $this->assertNotNull(

            $alert

        );





        $this->assertEquals(

            'eta_delay',

            $alert->type

        );


    }









    public function test_does_not_create_duplicate_alerts(): void
    {


        Notification::fake();



        $user = $this->authenticate();



        $trip = $this->createTrip(

            $user

        );





        $service = app(

            TripMonitoringService::class

        );





        $service->checkGpsLost(

            $trip

        );





        $service->checkGpsLost(

            $trip

        );





        $this->assertEquals(

            1,

            Alert::where(

                'trip_id',

                $trip->id

            )

            ->where(

                'type',

                'gps_lost'

            )

            ->count()

        );


    }


}