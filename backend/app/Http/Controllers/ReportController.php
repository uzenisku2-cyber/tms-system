<?php

namespace App\Http\Controllers;


use App\Models\Alert;

use App\Modules\Trips\Models\Trip;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Fleet\Models\Vehicle;
use App\Services\TripDistanceService;

use Carbon\Carbon;



class ReportController extends Controller
{

    protected TripDistanceService $distanceService;


    public function __construct(
        TripDistanceService $distanceService
    ) {

        $this->distanceService = $distanceService;

    }


    /*
    |--------------------------------------------------------------------------
    | Summary report
    |--------------------------------------------------------------------------
    */


    public function summary()
    {


        $totalTrips =

            Trip::count();





        $finishedTrips =

            Trip::where(

                function ($query) {


                    $query

                        ->where(
                            'status',
                            Trip::STATUS_FINISHED
                        )

                        ->orWhereNotNull(
                            'finished_at'
                        );


                }

            )

            ->count();






        $cancelledTrips =

            Trip::where(
                'status',
                Trip::STATUS_CANCELLED
            )

            ->count();






        $completionRate =

            $totalTrips > 0

                ?

                round(

                    ($finishedTrips / $totalTrips) * 100,

                    2

                )

                :

                0;







        $todayFinished =

            Trip::where(

                function ($query) {


                    $query

                        ->where(
                            'status',
                            Trip::STATUS_FINISHED
                        )

                        ->orWhereNotNull(
                            'finished_at'
                        );


                }

            )

            ->whereDate(

                'finished_at',

                Carbon::today()

            )

            ->count();








        

            $totalDistance = 0;


$allTrips =

    Trip::with(
        'locations'
    )

    ->get();



foreach ($allTrips as $trip) {


    $totalDistance +=

        $this->getTripDistance(
            $trip
        );


}







        $drivers =

            Driver::where(
                'active',
                true
            )

            ->count();







        $driverTrips =

            Trip::whereNotNull(
                'driver_id'
            )

            ->where(

                function ($query) {


                    $query

                        ->where(
                            'status',
                            Trip::STATUS_FINISHED
                        )

                        ->orWhereNotNull(
                            'finished_at'
                        );


                }

            )

            ->count();








        $vehicles =

            Vehicle::where(
                'active',
                true
            )

            ->count();








        $usedVehicles =

            Trip::whereNotNull(
                'vehicle_id'
            )

            ->where(

                function ($query) {


                    $query

                        ->where(
                            'status',
                            Trip::STATUS_FINISHED
                        )

                        ->orWhereNotNull(
                            'finished_at'
                        );


                }

            )

            ->distinct(
                'vehicle_id'
            )

            ->count(
                'vehicle_id'
            );








        $vehicleUtilization =

            $vehicles > 0

                ?

                round(

                    ($usedVehicles / $vehicles) * 100,

                    2

                )

                :

                0;








        $alerts = [


            'open' =>

                Alert::whereNull(
                    'resolved_at'
                )

                ->count(),



            'gps_lost' =>

                Alert::where(
                    'type',
                    'gps_lost'
                )

                ->count(),



            'eta_delay' =>

                Alert::where(
                    'type',
                    'eta_delay'
                )

                ->count(),



            'vehicle_idle' =>

                Alert::where(
                    'type',
                    'vehicle_idle'
                )

                ->count(),


        ];








        return response()->json([



            'trips' => [


                'total' =>

                    $totalTrips,


                'completed' =>

                    $finishedTrips,


                'cancelled' =>

                    $cancelledTrips,


                'finished_today' =>

                    $todayFinished,


                'completion_rate' =>

                    $completionRate,


            ],





            'distance' => [


                'total_km' =>

                    round(

                        (float) $totalDistance,

                        2

                    ),


            ],





            'drivers' => [


                'active' =>

                    $drivers,


                'completed_trips' =>

                    $driverTrips,


            ],





            'vehicles' => [


                'active' =>

                    $vehicles,


                'used' =>

                    $usedVehicles,


                'utilization' =>

                    $vehicleUtilization,


            ],





            'alerts' =>

                $alerts,



        ]);



    }
        /*
    |--------------------------------------------------------------------------
    | Driver performance report
    |--------------------------------------------------------------------------
    */


    public function drivers()
    {


        $drivers = Driver::with([

            'trips.locations',

        ])

        ->get()

        ->map(function (Driver $driver) {



            $completedDistance = 0;

            $allDistance = 0;

            $completedTrips = 0;





            foreach ($driver->trips as $trip) {


                $distance =

                    $this->getTripDistance(
                        $trip
                    );



                $allDistance += $distance;





                if (
                    $this->isCompletedTrip($trip)
                ) {


                    $completedDistance += $distance;

                    $completedTrips++;


                }


            }







            $alerts =

                Alert::whereIn(

                    'trip_id',

                    $driver->trips
                        ->pluck('id')

                )

                ->count();








            return [


                'id' =>

                    $driver->id,



                'name' =>

                    $driver->full_name,



                'active' =>

                    $driver->active,



                'statistics' => [


                    'total_trips' =>

                        $driver->trips->count(),



                    'completed_trips' =>

                        $completedTrips,



                    'active_trips' =>

                        $driver->trips

                            ->filter(function ($trip) {


                                return $this->isActiveTrip(
                                    $trip
                                );


                            })

                            ->count(),



                    'distance_completed_km' =>

                        round(
                            $completedDistance,
                            2
                        ),



                    'distance_all_km' =>

                        round(
                            $allDistance,
                            2
                        ),



                    'alerts' =>

                        $alerts,


                ],


            ];



        });







        return response()->json([


            'drivers' =>

                $drivers,


        ]);


    }









    /*
    |--------------------------------------------------------------------------
    | Vehicle performance report
    |--------------------------------------------------------------------------
    */


    public function vehicles()
    {


        $vehicles = Vehicle::with([

            'trips.locations',

        ])

        ->get()

        ->map(function (Vehicle $vehicle) {



            $completedDistance = 0;

            $allDistance = 0;

            $completedTrips = 0;






            foreach ($vehicle->trips as $trip) {


                $distance =

                    $this->getTripDistance(
                        $trip
                    );



                $allDistance += $distance;





                if (
                    $this->isCompletedTrip($trip)
                ) {


                    $completedDistance += $distance;

                    $completedTrips++;


                }


            }







            return [


                'id' =>

                    $vehicle->id,



                'registration' =>

                    $vehicle->registration_number,



                'model' =>

                    $vehicle->model,



                'statistics' => [


                    'total_trips' =>

                        $vehicle->trips->count(),



                    'completed_trips' =>

                        $completedTrips,



                    'active_trips' =>

                        $vehicle->trips

                            ->filter(function ($trip) {


                                return $this->isActiveTrip(
                                    $trip
                                );


                            })

                            ->count(),



                    'distance_completed_km' =>

                        round(
                            $completedDistance,
                            2
                        ),



                    'distance_all_km' =>

                        round(
                            $allDistance,
                            2
                        ),


                ],


            ];


        });







        return response()->json([


            'vehicles' =>

                $vehicles,


        ]);


    }
        /*
    |--------------------------------------------------------------------------
    | Driver detail report
    |--------------------------------------------------------------------------
    */


    public function driver(
        Driver $driver
    ) {


        $trips = $driver

            ->trips()

            ->with([

                'vehicle',

                'locations',

            ])

            ->latest()

            ->get();





        $completedDistance = 0;

        $allDistance = 0;

        $completedTrips = 0;





        foreach ($trips as $trip) {


            $distance =

                $this->getTripDistance(
                    $trip
                );



            $allDistance += $distance;





            if (
                $this->isCompletedTrip($trip)
            ) {


                $completedDistance += $distance;

                $completedTrips++;


            }


        }






        $alerts =

            Alert::whereIn(

                'trip_id',

                $trips->pluck('id')

            )

            ->count();








        return response()->json([



            'driver' => [



                'id' =>

                    $driver->id,



                'name' =>

                    $driver->full_name,



                'active' =>

                    $driver->active,


            ],





            'statistics' => [



                'total_trips' =>

                    $trips->count(),



                'completed_trips' =>

                    $completedTrips,



                'active_trips' =>

                    $trips

                        ->filter(function ($trip) {


                            return $this->isActiveTrip(
                                $trip
                            );


                        })

                        ->count(),



                'distance_completed_km' =>

                    round(
                        $completedDistance,
                        2
                    ),



                'distance_all_km' =>

                    round(
                        $allDistance,
                        2
                    ),



                'alerts' =>

                    $alerts,


            ],




                        'trips' =>

                $trips->map(function ($trip) {

                    $trip->calculated_distance_km =

                        round(
                            $this->getTripDistance($trip),
                            2
                        );


                    return $trip;

                }),



        ]);


    }









    /*
    |--------------------------------------------------------------------------
    | Vehicle detail report
    |--------------------------------------------------------------------------
    */


    public function vehicle(
        Vehicle $vehicle
    ) {


        $trips = $vehicle

            ->trips()

            ->with([

                'driver',

                'locations',

            ])

            ->latest()

            ->get();







        $completedDistance = 0;

        $allDistance = 0;

        $completedTrips = 0;






        foreach ($trips as $trip) {


            $distance =

                $this->getTripDistance(
                    $trip
                );



            $allDistance += $distance;





            if (
                $this->isCompletedTrip($trip)
            ) {


                $completedDistance += $distance;

                $completedTrips++;


            }


        }







        return response()->json([



            'vehicle' => [



                'id' =>

                    $vehicle->id,



                'registration' =>

                    $vehicle->registration_number,



                'model' =>

                    $vehicle->model,


            ],





            'statistics' => [



                'total_trips' =>

                    $trips->count(),



                'completed_trips' =>

                    $completedTrips,



                'active_trips' =>

                    $trips

                        ->filter(function ($trip) {


                            return $this->isActiveTrip(
                                $trip
                            );


                        })

                        ->count(),



                'distance_completed_km' =>

                    round(
                        $completedDistance,
                        2
                    ),



                'distance_all_km' =>

                    round(
                        $allDistance,
                        2
                    ),


            ],





                        'trips' =>

                $trips->map(function ($trip) {

                    $trip->calculated_distance_km =

                        round(
                            $this->getTripDistance($trip),
                            2
                        );


                    return $trip;

                }),



        ]);


    }
        /*
    |--------------------------------------------------------------------------
    | Trip status helpers
    |--------------------------------------------------------------------------
    */


    protected function isCompletedTrip(
        Trip $trip
    ): bool
    {


        return

            $trip->status === Trip::STATUS_FINISHED

            ||

            $trip->finished_at !== null;


    }









    protected function isActiveTrip(
        Trip $trip
    ): bool
    {


           return

        in_array(

            $trip->status,

            [

                Trip::STATUS_ASSIGNED,

                Trip::STATUS_STARTED,

            ],

            true

        )

        &&

        $trip->finished_at === null;


    }





    /*
    |--------------------------------------------------------------------------
    | Resolve trip distance
    |--------------------------------------------------------------------------
    */


  protected function getTripDistance(
        Trip $trip
    ): float
    {

        return $this->distanceService->calculate($trip);

    }


}
