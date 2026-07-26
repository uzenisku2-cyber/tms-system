<?php

namespace Tests\Feature;

use Tests\TestCase;

use App\Models\User;

use App\Modules\Drivers\Models\Driver;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Trips\Models\Trip;

use Laravel\Sanctum\Sanctum;

use Illuminate\Foundation\Testing\RefreshDatabase;


class ReportDetailTest extends TestCase
{

    use RefreshDatabase;



    public function test_driver_detail_report_returns_data(): void
    {


        $user = User::factory()->create();



        Sanctum::actingAs(
            $user
        );



        $driver = Driver::create([

            'user_id' => $user->id,

            'first_name' => 'John',

            'last_name' => 'Driver',

            'license_number' => 'DRIVER-DETAIL-001',

            'active' => true,

        ]);



        $vehicle = Vehicle::create([

            'user_id' => $user->id,

            'registration_number' => 'DETAIL-001',

            'vin' => 'VIN-DETAIL-001',

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

            "/api/v1/reports/drivers/{$driver->id}"

        );



        $response->assertStatus(200);



        $response->assertJsonStructure([

            'driver',

        ]);


    }





    public function test_vehicle_detail_report_returns_data(): void
    {


        $user = User::factory()->create();



        Sanctum::actingAs(
            $user
        );



        $vehicle = Vehicle::create([

            'user_id' => $user->id,

            'registration_number' => 'DETAIL-VEH-001',

            'vin' => 'VIN-VEH-DETAIL-001',

            'manufacturer' => 'Test',

            'model' => 'Truck',

            'year' => 2025,

            'fuel_type' => 'diesel',

            'mileage' => 1000,

            'active' => true,

        ]);



        $driver = Driver::create([

            'user_id' => $user->id,

            'first_name' => 'John',

            'last_name' => 'Driver',

            'license_number' => 'DRIVER-VEH-DETAIL-001',

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

            "/api/v1/reports/vehicles/{$vehicle->id}"

        );



        $response->assertStatus(200);



        $response->assertJsonStructure([

            'vehicle',

        ]);


    }


}