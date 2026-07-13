<?php

declare(strict_types=1);

namespace App\Http\Controllers;


use App\Models\Alert;

use App\Modules\Trips\Models\Trip;

use Carbon\Carbon;



class TripLiveController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Live trip status
    |--------------------------------------------------------------------------
    */


    public function show(
        Trip $trip
    ) {


        $trip->load([

            'driver',

            'vehicle',

        ]);




        $location = $trip

            ->locations()

            ->latest()

            ->first();





        $gpsAge = null;

        $gpsStatus = 'lost';





        if ($location) {


            $gpsAge = abs(

                Carbon::now('UTC')

                    ->diffInSeconds(

                        Carbon::parse(

                            $location->created_at

                        )

                    )

            );





            $gpsStatus = match (true) {


                $gpsAge < 60 =>

                    'fresh',



                $gpsAge < 300 =>

                    'stale',



                default =>

                    'lost',


            };


        }





        $alerts = Alert::where(

                'trip_id',

                $trip->id

            )

            ->whereNull(

                'resolved_at'

            )

            ->latest()

            ->get()

            ->map(function (Alert $alert) {


                return [


                    'id' =>

                        $alert->id,


                    'type' =>

                        $alert->type,


                    'severity' =>

                        $alert->severity,


                    'message' =>

                        $alert->message,


                    'created_at' =>

                        $alert->created_at,


                ];


            });







        return response()->json([


            'trip_id' =>

                $trip->id,



            'status' =>

                $trip->status,



            'driver' =>

                $trip->driver

                    ? [


                        'id' =>

                            $trip->driver->id,


                        'name' =>

                            trim(

                                $trip->driver->first_name

                                . ' '

                                .

                                $trip->driver->last_name

                            ),


                    ]

                    : null,



            'vehicle' =>

                $trip->vehicle

                    ? [


                        'id' =>

                            $trip->vehicle->id,


                        'registration_number' =>

                            $trip->vehicle->registration_number,


                        'model' =>

                            $trip->vehicle->model,


                    ]

                    : null,



            'location' =>

                $location

                    ? [


                        'latitude' =>

                            $location->latitude,


                        'longitude' =>

                            $location->longitude,


                        'speed' =>

                            $location->speed,


                        'heading' =>

                            $location->heading,


                        'updated_at' =>

                            $location->created_at,


                    ]

                    : null,



            'gps' => [


                'age_seconds' =>

                    $gpsAge,


                'status' =>

                    $gpsStatus,


                'attention' =>

                    $gpsStatus !== 'fresh',


            ],



            'alerts' =>

                $alerts,


        ]);


    }


}