<?php

namespace App\Console\Commands;

use App\Events\TripRealtimeBroadcast;
use App\Models\VehiclePosition;
use App\Modules\Fleet\Models\Vehicle;
use Illuminate\Console\Command;

class SimulateGps extends Command
{
    protected $signature =
        'gps:simulate
        {trip=6}';

    protected $description =
        'Simulate vehicle GPS movement';

    public function handle()
    {

        $trip =
            $this->argument('trip');

        /*
         * Vehicles to simulate
         */

        $vehicles =
            Vehicle::whereIn(
                'id',
                [
                    3,
                    4,
                ]
            )
                ->get();

        if (
            $vehicles->count() === 0
        ) {

            $this->error(
                'No vehicles found'
            );

            return Command::FAILURE;

        }

        /*
         * Routes per vehicle
         */

        $routes = [

            // MAN TGX

            4 => [

                [
                    50.170,
                    14.560,
                ],

                [
                    50.171,
                    14.561,
                ],

                [
                    50.172,
                    14.563,
                ],

                [
                    50.174,
                    14.566,
                ],

                [
                    50.176,
                    14.569,
                ],

                [
                    50.178,
                    14.572,
                ],

            ],

            // SKODA OCTAVIA

            3 => [

                [
                    50.120,
                    14.450,
                ],

                [
                    50.121,
                    14.452,
                ],

                [
                    50.123,
                    14.455,
                ],

                [
                    50.125,
                    14.458,
                ],

                [
                    50.127,
                    14.461,
                ],

                [
                    50.129,
                    14.465,
                ],

            ],

        ];

        /*
 * RUN FLEET SIMULATION STEP BY STEP
 */

        $maxSteps = 0;

        foreach ($routes as $route) {
            $maxSteps = max(
                $maxSteps,
                count($route)
            );
        }

        for (
            $step = 0;
            $step < $maxSteps;
            $step++
        ) {

            foreach (
                $vehicles as $vehicleModel
            ) {

                $vehicle =
                    $vehicleModel->id;

                if (
                    ! isset($routes[$vehicle][$step])
                ) {
                    continue;
                }

                $point =
                    $routes[$vehicle][$step];

                $latitude =
                    $point[0];

                $longitude =
                    $point[1];

                $speed =
                    rand(
                        50,
                        90
                    );

                $heading =
                    match (true) {

                        $longitude < 14.455 => 90,

                        $longitude < 14.465 => 95,

                        default => 100,

                    };

                $status =
                    $speed > 5
                    ? 'MOVING'
                    : 'STOPPED';

                $lastSeen =
                    now()
                        ->toDateTimeString();

                VehiclePosition::create([

                    'trip_id' => $trip,

                    'vehicle_id' => $vehicle,

                    'latitude' => $latitude,

                    'longitude' => $longitude,

                    'speed' => $speed,

                    'heading' => $heading,

                ]);

                event(

                    new TripRealtimeBroadcast(

                        'trip.'.$trip,

                        [

                            'trip_id' => $trip,

                            'vehicle_id' => $vehicle,

                            'vehicle_type' => $vehicleModel->vehicle_type,

                            'manufacturer' => $vehicleModel->manufacturer,

                            'model' => $vehicleModel->model,

                            'registration_number' => $vehicleModel->registration_number,

                            'color' => $vehicleModel->color,

                            'latitude' => $latitude,

                            'longitude' => $longitude,

                            'speed' => $speed,

                            'heading' => $heading,

                            'status' => $status,

                            'last_seen_at' => $lastSeen,

                        ]

                    )

                );

                $this->info(
                    "Vehicle {$vehicle}: GPS {$latitude}, {$longitude}"
                );

            }

            sleep(2);

        }

    }
}
