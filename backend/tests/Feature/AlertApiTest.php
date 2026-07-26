<?php

namespace Tests\Feature;


use Tests\TestCase;


use App\Models\User;
use App\Models\Alert;

use App\Modules\Trips\Models\Trip;


use Illuminate\Foundation\Testing\RefreshDatabase;

use Laravel\Sanctum\Sanctum;



class AlertApiTest extends TestCase
{


    use RefreshDatabase;




    protected function createAlert(
        User $user,
        array $attributes = []
    ): Alert {


        $trip = Trip::create([

            'user_id' => $user->id,

            'origin' => 'Praha',

            'destination' => 'Brno',

            'origin_lat' => 50.0755,

            'origin_lng' => 14.4378,

            'destination_lat' => 49.1951,

            'destination_lng' => 16.6068,

            'status' => Trip::STATUS_STARTED,

        ]);



        return Alert::create(array_merge([

            'trip_id' => $trip->id,

            'user_id' => $user->id,

            'type' => 'gps_lost',

            'severity' => 'warning',

            'message' => 'GPS signal lost',

        ], $attributes));


    }





    public function test_alert_list_returns_data(): void
    {

        $user = User::factory()->create();


        Sanctum::actingAs($user);



        $this->createAlert($user);



        $response = $this->getJson(

            '/api/v1/alerts'

        );



        $response->assertStatus(200);



        $response->assertJsonStructure([

            'data' => [

                'unread_count',

                'items',

                'meta',

            ],

        ]);

    }





    public function test_open_alerts_returns_unresolved_only(): void
    {

        $user = User::factory()->create();


        Sanctum::actingAs($user);



        $this->createAlert($user);



        $resolved = $this->createAlert(

            $user,

            [

                'type' => 'eta_delay',

                'resolved_at' => now(),

            ]

        );



        $response = $this->getJson(

            '/api/v1/alerts/open'

        );



        $response->assertStatus(200);



        $response->assertJsonPath(

            'data.count',

            1

        );

    }





    public function test_unread_alerts_returns_only_unread(): void
    {

        $user = User::factory()->create();


        Sanctum::actingAs($user);



        $this->createAlert($user);



        $this->createAlert(

            $user,

            [

                'type' => 'vehicle_idle',

                'read_at' => now(),

            ]

        );



        $response = $this->getJson(

            '/api/v1/alerts/unread'

        );



        $response->assertStatus(200);



        $response->assertJsonPath(

            'data.count',

            1

        );

    }





    public function test_alert_can_be_resolved(): void
    {

        $user = User::factory()->create();


        Sanctum::actingAs($user);



        $alert = $this->createAlert(

            $user

        );



        $response = $this->patchJson(

            "/api/v1/alerts/{$alert->id}/resolve"

        );



        $response->assertStatus(200);



        $this->assertDatabaseHas(

            'alerts',

            [

                'id' => $alert->id,

            ]

        );


        $this->assertNotNull(

            Alert::find($alert->id)->resolved_at

        );

    }





    public function test_alert_summary_returns_counts(): void
    {

        $user = User::factory()->create();


        Sanctum::actingAs($user);



        $this->createAlert(

            $user,

            [

                'severity' => 'critical',

            ]

        );



        $response = $this->getJson(

            '/api/v1/alerts/summary'

        );



        $response->assertStatus(200);



        $response->assertJsonStructure([

            'data' => [

                'total',

                'unresolved',

                'critical',

                'warning',

                'info',

            ],

        ]);

    }


}