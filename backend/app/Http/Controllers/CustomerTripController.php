<?php

namespace App\Http\Controllers;

use App\Modules\Trips\Models\Trip;


class CustomerTripController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Customer trips
    |--------------------------------------------------------------------------
    */

    public function index()
    {

        $trips = Trip::where(
                'user_id',
                auth()->id()
            )
            ->with([
                'driver',
                'vehicle',
                'pod',
            ])
            ->latest()
            ->get();



        return response()->json([

            'trips' => $trips->map(function ($trip) {


                return [

                    'id' =>
                        $trip->id,

                    'origin' =>
                        $trip->origin,

                    'destination' =>
                        $trip->destination,

                    'status' =>
                        $trip->status,


                    'driver' =>
                        $trip->driver
                            ? $trip->driver->first_name
                                . ' '
                                . $trip->driver->last_name
                            : null,


                    'vehicle' =>
                        $trip->vehicle
                            ? $trip->vehicle->registration_number
                            : null,


                    'pod' =>
                        $trip->pod
                            ? [

                                'recipient' =>
                                    $trip->pod->recipient,

                                'delivered_at' =>
                                    $trip->pod->delivered_at,

                            ]
                            : null,

                ];

            }),

        ]);

    }





    /*
    |--------------------------------------------------------------------------
    | Customer trip detail
    |--------------------------------------------------------------------------
    */

    public function show(Trip $trip)
    {


        if ($trip->user_id !== auth()->id()) {

            abort(
                403,
                'Trip does not belong to customer'
            );

        }



        return response()->json(

            $trip->load([

                'driver',

                'vehicle',

                'events.user',

                'pod',

            ])

        );

    }


}