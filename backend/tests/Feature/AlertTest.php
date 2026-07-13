<?php

namespace Tests\Feature;


use Tests\TestCase;


use App\Models\User;

use App\Models\Alert;

use App\Models\TripLocation;


use App\Modules\Trips\Models\Trip;


use App\Services\TripMonitoringService;


use Illuminate\Foundation\Testing\RefreshDatabase;


use Laravel\Sanctum\Sanctum;



class AlertTest extends TestCase
{


    use RefreshDatabase;




    protected function createStartedTrip(

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


            'status' => Trip::STATUS_STARTED,


        ], $attributes));


    }





    public function test_gps_lost_creates_alert(): void

    {


        $user = User::factory()->create();



        Sanctum::actingAs(

            $user

        );



        $trip = $this->createStartedTrip(

            $user

        );



        app(TripMonitoringService::class)

            ->checkGpsLost(

                $trip

            );



        $this->assertDatabaseHas(

            'alerts',

            [

                'trip_id' => $trip->id,

                'type' => 'gps_lost',

            ]

        );


    }






    public function test_eta_delay_creates_alert(): void

    {


        $user = User::factory()->create();



        Sanctum::actingAs(

            $user

        );



        $trip = $this->createStartedTrip(

            $user,

            [

                'scheduled_at' => now()->subHour(),

            ]

        );



        app(TripMonitoringService::class)

            ->checkEtaDelay(

                $trip

            );



        $this->assertDatabaseHas(

            'alerts',

            [

                'trip_id' => $trip->id,

                'type' => 'eta_delay',

            ]

        );


    }






    public function test_vehicle_idle_creates_alert(): void

    {


        $user = User::factory()->create();



        Sanctum::actingAs(

            $user

        );



        $trip = $this->createStartedTrip(

            $user

        );



        $location = TripLocation::create(

            [

                'trip_id' => $trip->id,

                'latitude' => 50.0755,

                'longitude' => 14.4378,

                'speed' => 0,

            ]

        );



        $location->created_at = now()->subMinutes(40);

        $location->updated_at = now()->subMinutes(40);

        $location->save();



        app(TripMonitoringService::class)

            ->checkVehicleIdle(

                $trip

            );



        $this->assertDatabaseHas(

            'alerts',

            [

                'trip_id' => $trip->id,

                'type' => 'vehicle_idle',

            ]

        );


    }






    public function test_alert_can_be_marked_as_read(): void

    {


        $user = User::factory()->create();



        Sanctum::actingAs(

            $user

        );



        $trip = $this->createStartedTrip(

            $user

        );



        $alert = Alert::create(

            [

                'trip_id' => $trip->id,

                'user_id' => $user->id,

                'type' => 'gps_lost',

                'severity' => 'warning',

                'message' => 'GPS signal lost',

            ]

        );



        $response = $this->patchJson(

            "/api/v1/alerts/{$alert->id}/read"

        );



        $response->assertStatus(200);



        $this->assertDatabaseHas(

            'alerts',

            [

                'id' => $alert->id,

                'read_at' => now(),

            ]

        );


    }



}