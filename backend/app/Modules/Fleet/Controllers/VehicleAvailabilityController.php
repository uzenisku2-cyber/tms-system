<?php

namespace App\Modules\Fleet\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fleet\Models\Vehicle;


class VehicleAvailabilityController extends Controller
{

    public function show(Vehicle $vehicle)
    {

        $activeTrips = $vehicle
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

            'vehicle_id' => $vehicle->id,

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