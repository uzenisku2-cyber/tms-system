<?php

namespace App\Modules\Drivers\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Drivers\Models\Driver;


class DriverAvailabilityController extends Controller
{

    public function show(Driver $driver)
    {

        $activeTrips = $driver
            ->trips()
            ->whereIn(
                'status',
                [
                    'assigned',
                    'started'
                ]
            )
            ->get();


        return response()->json([

            'driver_id' => $driver->id,

            'available' =>
                $activeTrips->count() === 0,

            'active_trips' =>
                $activeTrips->count(),

            'current_trips' =>
                $activeTrips->map(function($trip){

                    return [

                        'id' => $trip->id,

                        'origin' =>
                            $trip->origin,

                        'destination' =>
                            $trip->destination,

                        'status' =>
                            $trip->status,

                    ];

                }),

        ]);

    }

}