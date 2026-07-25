<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Trips\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
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

        User $user,

        string $status

    ): Trip {

        $driver = Driver::firstOrCreate(

            [
                'user_id' => $user->id,
            ],
            [

                'first_name' => 'Jan',

                'last_name' => 'Novak',

                'license_number' => 'CZ'.uniqid(),

                'active' => true,

            ]
        );

        $vehicle = Vehicle::create([

            'user_id' => $user->id,

            'registration_number' => '1AB'.strtoupper(substr(uniqid(), -5)),

            'manufacturer' => 'Skoda',

            'model' => 'Octavia',

            'vin' => strtoupper(

                substr(

                    str_replace('.', '', uniqid('VIN')),

                    0,

                    17

                )

            ),

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

            'status' => $status,

        ]);

    }

    public function test_dashboard_returns_structure(): void
    {

        $this->authenticate();

        $response = $this->getJson(

            '/api/v1/dashboard'

        );

        $response->assertStatus(200);

        $response->assertJsonStructure([

            'data' => [

                'trips',

                'drivers',

                'vehicles',

                'active_trips',

                'alerts',

                'operations',

                'fleet_health',

                'operations_live',

                'notifications',

            ],

        ]);

    }

    public function test_dashboard_returns_fleet_health(): void
    {

        $user = $this->authenticate();

        $this->createTrip(

            $user,

            Trip::STATUS_STARTED

        );

        $response = $this->getJson(

            '/api/v1/dashboard'

        );

        $response->assertStatus(200);

        $response->assertJsonStructure([

            'data' => [

                'fleet_health' => [

                    'vehicles_total',

                    'vehicles_active',

                    'vehicles_idle',

                    'gps' => [

                        'fresh',

                        'stale',

                        'lost',

                    ],

                ],

            ],

        ]);

    }

    public function test_dashboard_returns_operations_live(): void
    {

        $user = $this->authenticate();

        $this->createTrip(

            $user,

            Trip::STATUS_STARTED

        );

        $response = $this->getJson(

            '/api/v1/dashboard'

        );

        $response->assertStatus(200);

        $response->assertJsonStructure([

            'data' => [

                'operations_live' => [

                    'active_trips',

                    'gps' => [

                        'fresh',

                        'stale',

                        'lost',

                    ],

                    'eta_delays',

                    'critical_alerts',

                ],

            ],

        ]);

        $response->assertJsonPath(

            'data.operations_live.active_trips',

            1

        );

    }

    public function test_dashboard_counts_trips(): void
    {

        $user = $this->authenticate();

        $this->createTrip(

            $user,

            Trip::STATUS_PLANNED

        );

        $this->createTrip(

            $user,

            Trip::STATUS_STARTED

        );

        $response = $this->getJson(

            '/api/v1/dashboard'

        );
        $response->assertStatus(200);

        $response->assertJsonPath(

            'data.trips.total',

            2

        );

        $response->assertJsonPath(

            'data.trips.planned',

            1

        );

        $response->assertJsonPath(

            'data.trips.started',

            1

        );

    }

    public function test_dashboard_returns_operations_data(): void
    {

        $user = $this->authenticate();

        $this->createTrip(

            $user,

            Trip::STATUS_STARTED

        );

        $response = $this->getJson(

            '/api/v1/dashboard'

        );

        $response->assertStatus(200);

        $response->assertJsonStructure([

            'data' => [

                'operations' => [

                    'today' => [

                        'created',

                        'finished',

                        'cancelled',

                    ],

                    'fleet' => [

                        'total',

                        'active',

                    ],

                ],

            ],

        ]);

    }

    public function test_dashboard_metrics_returns_data(): void
    {

        $user = $this->authenticate();

        $this->createTrip(

            $user,

            Trip::STATUS_STARTED

        );

        $response = $this->getJson(

            '/api/v1/dashboard/metrics'

        );

        $response->assertStatus(200);

        $response->assertJsonStructure([

            'data' => [

                'trips',

                'alerts',

                'monitoring',

                'notifications',

            ],

        ]);

        $response->assertJsonPath(

            'data.trips.active',

            1

        );

    }

    public function test_dashboard_kpi_returns_counts(): void
    {

        $user = $this->authenticate();

        $trip = $this->createTrip(

            $user,

            Trip::STATUS_STARTED

        );

        Alert::create([

            'trip_id' => $trip->id,

            'user_id' => $user->id,

            'type' => 'gps_lost',

            'severity' => 'warning',

            'message' => 'GPS lost',

        ]);

        $response = $this->getJson(

            '/api/v1/dashboard/kpi'

        );

        $response->assertStatus(200);

        $response->assertJsonStructure([

            'active_trips',

            'finished_today',

            'open_alerts',

            'gps_lost',

            'eta_delay',

            'vehicle_idle',

            'critical_alerts',

        ]);

        $response->assertJson([

            'active_trips' => 1,

            'open_alerts' => 1,

            'gps_lost' => 1,

        ]);

    }
}
