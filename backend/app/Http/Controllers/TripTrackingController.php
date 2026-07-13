<?php

declare(strict_types=1);

namespace App\Http\Controllers;


use App\Models\TripLocation;

use App\Services\TripMonitoringService;

use App\Modules\Trips\Models\Trip;

use Carbon\Carbon;

use Illuminate\Http\Request;



class TripTrackingController extends Controller
{


    protected TripMonitoringService $monitoring;



    public function __construct(
        TripMonitoringService $monitoring
    ) {

        $this->monitoring = $monitoring;

    }





    /*
    |--------------------------------------------------------------------------
    | Store GPS location
    |--------------------------------------------------------------------------
    */


    public function store(
        Request $request,
        Trip $trip
    ) {


        $validated = $request->validate([


            'latitude' => [

                'required',

                'numeric',

                'between:-90,90',

            ],


            'longitude' => [

                'required',

                'numeric',

                'between:-180,180',

            ],


            'speed' => [

                'nullable',

                'integer',

                'min:0',

            ],


            'heading' => [

                'nullable',

                'integer',

                'between:0,360',

            ],


        ]);





        $location = TripLocation::create([


            'trip_id' =>

                $trip->id,


            'latitude' =>

                $validated['latitude'],


            'longitude' =>

                $validated['longitude'],


            'speed' =>

                $validated['speed'] ?? null,


            'heading' =>

                $validated['heading'] ?? null,


        ]);





        /*
        |--------------------------------------------------------------------------
        | Run monitoring checks
        |--------------------------------------------------------------------------
        */


        $freshTrip = $trip->fresh();




        // GPS signal lost

        $this->monitoring->checkGpsLost(

            $freshTrip

        );





        // ETA delay

        $this->monitoring->checkEtaDelay(

            $freshTrip

        );





        // Vehicle idle

        $this->monitoring->checkVehicleIdle(

            $freshTrip

        );







        return response()->json([


            'status' =>

                'location_saved',



            'data' =>

                $location,


        ], 201);


    }







    /*
    |--------------------------------------------------------------------------
    | GPS history
    |--------------------------------------------------------------------------
    */


    public function index(
        Trip $trip
    ) {


        $locations = $trip

            ->locations()

            ->latest()

            ->get();





        $current = $locations->first();





        return response()->json([


            'trip_id' =>

                $trip->id,



            'current_position' =>

                $current

                    ? [


                        'latitude' =>

                            $current->latitude,


                        'longitude' =>

                            $current->longitude,


                    ]

                    : null,



            'history' =>

                $locations,


        ]);


    }







    /*
    |--------------------------------------------------------------------------
    | Active trips live positions
    |--------------------------------------------------------------------------
    */


    public function activeLive()
    {


        $trips = Trip::with([

                'driver',

                'vehicle',

            ])

            ->where(

                'status',

                Trip::STATUS_STARTED

            )

            ->get();







        $data = $trips->map(function (Trip $trip) {



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







            return [


                'trip_id' =>

                    $trip->id,



                'status' =>

                    $trip->status,



                'origin' =>

                    $trip->origin,



                'destination' =>

                    $trip->destination,



                'driver' => $trip->driver

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



                'vehicle' => $trip->vehicle

                    ? [


                        'id' =>

                            $trip->vehicle->id,


                        'registration' =>

                            $trip->vehicle->registration_number,


                    ]

                    : null,



                'location' => $location

                    ? [


                        'latitude' =>

                            $location->latitude,


                        'longitude' =>

                            $location->longitude,


                        'speed' =>

                            $location->speed,


                        'heading' =>

                            $location->heading,


                        'recorded_at' =>

                            $location->created_at,


                    ]

                    : null,



                'gps_age_seconds' =>

                    $gpsAge,



                'gps_status' =>

                    $gpsStatus,



                'attention' =>

                    $gpsStatus !== 'fresh',



                'attention_reason' => match ($gpsStatus) {


                    'fresh' =>

                        null,



                    'stale' =>

                        'GPS signal delayed',



                    'lost' =>

                        'GPS signal lost',



                    default =>

                        'Unknown GPS status',


                },


            ];


        });







        return response()->json([


            'active_trips' =>

                $data,


        ]);


    }


}