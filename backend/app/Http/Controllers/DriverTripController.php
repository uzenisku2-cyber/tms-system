<?php

namespace App\Http\Controllers;


use App\Services\AlertService;


use Illuminate\Http\Request;


use App\Modules\Drivers\Models\Driver;

use App\Modules\Trips\Models\Trip;

use App\Modules\Trips\Models\TripEvent;



class DriverTripController extends Controller
{


    protected AlertService $alertService;



    public function __construct(
        AlertService $alertService
    ) {

        $this->alertService = $alertService;

    }





    /*
    |--------------------------------------------------------------------------
    | Logged driver trips
    |--------------------------------------------------------------------------
    */


    public function index()
    {


        $driver = $this->getDriver();



        $trips = $driver

            ->trips()

            ->with([

                'vehicle'

            ])

            ->latest()

            ->get();




        return response()->json([


            'driver_id' =>
                $driver->id,


            'trips' =>
                $trips->map(function ($trip) {


                    return [


                        'id' =>
                            $trip->id,


                        'origin' =>
                            $trip->origin,


                        'destination' =>
                            $trip->destination,


                        'status' =>
                            $trip->status,


                        'vehicle' =>
                            $trip->vehicle
                                ? $trip->vehicle->registration_number
                                : null,


                    ];


                }),


        ]);


    }





    /*
    |--------------------------------------------------------------------------
    | Start trip
    |--------------------------------------------------------------------------
    */


    public function start(
        Trip $trip
    )
    {


        $driver = $this->getDriver();



        $this->authorizeDriverTrip(

            $trip,

            $driver

        );




        if (

            ! $trip->canChangeStatus(

                Trip::STATUS_STARTED

            )

        ) {


            return response()->json([


                'message' =>
                    'Trip cannot be started',


                'current_status' =>
                    $trip->status,


            ], 422);


        }





        $oldStatus =
            $trip->status;




        $trip->update([


            'status' =>
                Trip::STATUS_STARTED,


            'started_at' =>
                now(),


        ]);





        TripEvent::create([


            'trip_id' =>
                $trip->id,


            'user_id' =>
                auth()->id(),


            'old_status' =>
                $oldStatus,


            'new_status' =>
                Trip::STATUS_STARTED,


        ]);





        $this->alertService->statusChanged(

            $trip,

            $oldStatus,

            Trip::STATUS_STARTED

        );





        return response()->json([


            'status' =>
                'started',


            'data' =>
                $trip->fresh(),


        ]);


    }





    /*
    |--------------------------------------------------------------------------
    | Finish trip
    |--------------------------------------------------------------------------
    */


    public function finish(
        Trip $trip
    )
    {


        $driver = $this->getDriver();




        $this->authorizeDriverTrip(

            $trip,

            $driver

        );





        if (

            ! $trip->canChangeStatus(

                Trip::STATUS_FINISHED

            )

        ) {


            return response()->json([


                'message' =>
                    'Trip cannot be finished',


                'current_status' =>
                    $trip->status,


            ], 422);


        }





        $oldStatus =
            $trip->status;




        $trip->update([


            'status' =>
                Trip::STATUS_FINISHED,


            'finished_at' =>
                now(),


        ]);





        TripEvent::create([


            'trip_id' =>
                $trip->id,


            'user_id' =>
                auth()->id(),


            'old_status' =>
                $oldStatus,


            'new_status' =>
                Trip::STATUS_FINISHED,


        ]);





        $this->alertService->statusChanged(

            $trip,

            $oldStatus,

            Trip::STATUS_FINISHED

        );





        return response()->json([


            'status' =>
                'finished',


            'data' =>
                $trip->fresh(),


        ]);


    }





    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */


    private function getDriver()
    {


        return Driver::where(

            'user_id',

            auth()->id()

        )->firstOrFail();


    }





    private function authorizeDriverTrip(

        Trip $trip,

        Driver $driver

    ) {


        if (

            $trip->driver_id !== $driver->id

        ) {


            abort(

                403,

                'Trip does not belong to driver'

            );


        }


    }


}