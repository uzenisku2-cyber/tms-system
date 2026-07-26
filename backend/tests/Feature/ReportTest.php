<?php

namespace Tests\Feature;

use Tests\TestCase;

use App\Models\User;

use App\Modules\Drivers\Models\Driver;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Trips\Models\Trip;

use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;


class ReportTest extends TestCase
{

    use RefreshDatabase;



    public function test_summary_report_returns_structure(): void
    {


        $user = User::factory()->create();



        Sanctum::actingAs(
            $user
        );



        $response = $this->getJson(
            '/api/v1/reports/summary'
        );



        $response->assertStatus(200);



        $response->assertJsonStructure([

            'trips',

            'distance',

            'drivers',

            'vehicles',

            'alerts',

        ]);


    }





    public function test_summary_report_calculates_data(): void
    {


        $user = User::factory()->create();



        Sanctum::actingAs(
            $user
        );



        $driver = Driver::create([

            'user_id' => $user->id,

            'first_name' => 'John',

            'last_name' => 'Driver',

            'license_number' => 'TEST-001',

            'active' => true,

        ]);



        $vehicle = Vehicle::create([

            'user_id' => $user->id,

            'registration_number' => 'TEST-001',

            'vin' => 'VINTEST001',

            'manufacturer' => 'Test',

            'model' => 'Vehicle',

            'year' => 2025,

            'fuel_type' => 'diesel',

            'mileage' => 1000,

            'active' => true,

        ]);



        Trip::create([

            'user_id' => $user->id,

            'driver_id' => $driver->id,

            'vehicle_id' => $vehicle->id,

            'origin' => 'Praha',

            'destination' => 'Brno',

            'status' => Trip::STATUS_FINISHED,

            'finished_at' => now(),

            'distance_km' => 185,

        ]);



        $response = $this->getJson(
            '/api/v1/reports/summary'
        );



        $response->assertStatus(200);



        $response->assertJsonPath(
            'trips.total',
            1
        );



        $response->assertJsonPath(
            'distance.total_km',
            185
        );



        $response->assertJsonPath(
            'drivers.active',
            1
        );



        $response->assertJsonPath(
            'vehicles.active',
            1
        );


    }





    public function test_drivers_report_returns_data(): void
    {


        $user = User::factory()->create();



        Sanctum::actingAs(
            $user
        );



        $driver = Driver::create([

            'user_id' => $user->id,

            'first_name' => 'John',

            'last_name' => 'Driver',

            'license_number' => 'DRIVER-001',

            'active' => true,

        ]);



        $vehicle = Vehicle::create([

            'user_id' => $user->id,

            'registration_number' => 'TEST-001',

            'vin' => 'VIN-REPORT-001',

            'manufacturer' => 'Test',

            'model' => 'Truck',

            'year' => 2025,

            'fuel_type' => 'diesel',

            'mileage' => 1000,

            'active' => true,

        ]);



        Trip::create([

            'user_id' => $user->id,

            'driver_id' => $driver->id,

            'vehicle_id' => $vehicle->id,

            'origin' => 'Praha',

            'destination' => 'Brno',

            'status' => Trip::STATUS_FINISHED,

            'finished_at' => now(),

            'distance_km' => 185,

        ]);



        $response = $this->getJson(
            '/api/v1/reports/drivers'
        );



        $response->assertStatus(200);



        $response->assertJsonStructure([

            'drivers',

        ]);


    }





    public function test_vehicles_report_returns_data(): void
    {


        $user = User::factory()->create();



        Sanctum::actingAs(
            $user
        );



        $vehicle = Vehicle::create([

            'user_id' => $user->id,

            'registration_number' => 'VEH-001',

            'vin' => 'VIN-VEH-001',

            'manufacturer' => 'Test',

            'model' => 'Truck',

            'year' => 2025,

            'fuel_type' => 'diesel',

            'mileage' => 5000,

            'active' => true,

        ]);



        $driver = Driver::create([

            'user_id' => $user->id,

            'first_name' => 'John',

            'last_name' => 'Driver',

            'license_number' => 'DRIVER-VEH-001',

            'active' => true,

        ]);



        Trip::create([

            'user_id' => $user->id,

            'driver_id' => $driver->id,

            'vehicle_id' => $vehicle->id,

            'origin' => 'Praha',

            'destination' => 'Brno',

            'status' => Trip::STATUS_FINISHED,

            'finished_at' => now(),

            'distance_km' => 185,

        ]);



        $response = $this->getJson(
            '/api/v1/reports/vehicles'
        );



        $response->assertStatus(200);



        $response->assertJsonStructure([

            'vehicles',

        ]);


    }


}