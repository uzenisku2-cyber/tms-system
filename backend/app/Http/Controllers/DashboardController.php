<?php

namespace App\Http\Controllers;


use App\Models\Alert;

use App\Http\Resources\DashboardResource;
use App\Http\Resources\DashboardMetricsResource;

use App\Modules\Trips\Models\Trip;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Fleet\Models\Vehicle;

use Carbon\Carbon;



class DashboardController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Dashboard statistics
    |--------------------------------------------------------------------------
    */


    public function index()
    {


        $trips = [


            'total' =>

                Trip::count(),


            'planned' =>

                Trip::where(
                    'status',
                    Trip::STATUS_PLANNED
                )->count(),


            'assigned' =>

                Trip::where(
                    'status',
                    Trip::STATUS_ASSIGNED
                )->count(),


            'started' =>

                Trip::where(
                    'status',
                    Trip::STATUS_STARTED
                )->count(),


            'finished' =>

                Trip::where(
                    'status',
                    Trip::STATUS_FINISHED
                )->count(),


            'cancelled' =>

                Trip::where(
                    'status',
                    Trip::STATUS_CANCELLED
                )->count(),


        ];





        $drivers = [


            'total' =>

                Driver::count(),


            'active' =>

                Driver::where(
                    'active',
                    true
                )->count(),


            'available' =>

                Driver::where(
                    'active',
                    true
                )
                ->get()
                ->filter(function (Driver $driver) {

                    return ! $driver->hasActiveTrip();

                })
                ->count(),


            'busy' =>

                Driver::where(
                    'active',
                    true
                )
                ->get()
                ->filter(function (Driver $driver) {

                    return $driver->hasActiveTrip();

                })
                ->count(),


        ];







        $vehicles = [


            'total' =>

                Vehicle::count(),


            'active' =>

                Vehicle::where(
                    'active',
                    true
                )->count(),


            'available' =>

                Vehicle::where(
                    'active',
                    true
                )
                ->get()
                ->filter(function (Vehicle $vehicle) {

                    return ! $vehicle->hasActiveTrip();

                })
                ->count(),


            'busy' =>

                Vehicle::where(
                    'active',
                    true
                )
                ->get()
                ->filter(function (Vehicle $vehicle) {

                    return $vehicle->hasActiveTrip();

                })
                ->count(),


        ];







        $activeTrips =

            Trip::where(
                'status',
                Trip::STATUS_STARTED
            )
            ->with([

                'driver',

                'vehicle',

            ])
            ->latest()
            ->get();







        $alerts = [


            'open' =>

                Alert::whereNull(
                    'resolved_at'
                )->count(),


            'critical' =>

                Alert::whereNull(
                    'resolved_at'
                )
                ->where(
                    'severity',
                    'critical'
                )
                ->count(),


            'latest' =>

                Alert::with([

                    'trip',

                ])
                ->whereNull(
                    'resolved_at'
                )
                ->latest()
                ->limit(5)
                ->get(),


        ];







        $notifications = [


            'unread' =>

                auth()->user()
                    ->unreadNotifications()
                    ->count(),


            'latest' =>

                auth()->user()
                    ->notifications()
                    ->latest()
                    ->limit(5)
                    ->get(),


        ];







        $operations = [


            'today' => [


                'created' =>

                    Trip::whereDate(
                        'created_at',
                        Carbon::today()
                    )->count(),



                'finished' =>

                    Trip::where(
                        'status',
                        Trip::STATUS_FINISHED
                    )
                    ->whereDate(
                        'finished_at',
                        Carbon::today()
                    )
                    ->count(),



                'cancelled' =>

                    Trip::where(
                        'status',
                        Trip::STATUS_CANCELLED
                    )
                    ->whereDate(
                        'cancelled_at',
                        Carbon::today()
                    )
                    ->count(),


            ],



            'fleet' => [


                'total' =>

                    Vehicle::count(),



                'active' =>

                    Vehicle::where(
                        'active',
                        true
                    )->count(),


            ],


        ];







        return new DashboardResource([


            'trips' =>

                $trips,


            'drivers' =>

                $drivers,


            'vehicles' =>

                $vehicles,


            'active_trips' =>

                $activeTrips,


            'alerts' =>

                $alerts,


            'operations' =>

                $operations,


            'notifications' =>

                $notifications,


        ]);


    }









    /*
    |--------------------------------------------------------------------------
    | Dashboard metrics
    |--------------------------------------------------------------------------
    */


    public function metrics()
    {


        $trips = [


            'active' =>

                Trip::where(
                    'status',
                    Trip::STATUS_STARTED
                )->count(),


            'finished_today' =>

                Trip::where(
                    'status',
                    Trip::STATUS_FINISHED
                )
                ->whereDate(
                    'finished_at',
                    Carbon::today()
                )
                ->count(),


            'cancelled_today' =>

                Trip::where(
                    'status',
                    Trip::STATUS_CANCELLED
                )
                ->whereDate(
                    'cancelled_at',
                    Carbon::today()
                )
                ->count(),


        ];







        $alerts = [


            'open' =>

                Alert::whereNull(
                    'resolved_at'
                )->count(),


            'critical' =>

                Alert::whereNull(
                    'resolved_at'
                )
                ->where(
                    'severity',
                    'critical'
                )
                ->count(),


            'resolved_today' =>

                Alert::whereDate(
                    'resolved_at',
                    Carbon::today()
                )->count(),


        ];







        $monitoring = [


            'gps_lost' =>

                Alert::where(
                    'type',
                    'gps_lost'
                )
                ->whereNull(
                    'resolved_at'
                )
                ->count(),


            'eta_delay' =>

                Alert::where(
                    'type',
                    'eta_delay'
                )
                ->whereNull(
                    'resolved_at'
                )
                ->count(),


            'vehicle_idle' =>

                Alert::where(
                    'type',
                    'vehicle_idle'
                )
                ->whereNull(
                    'resolved_at'
                )
                ->count(),


        ];







        $notifications = [


            'unread' =>

                auth()->user()
                    ->unreadNotifications()
                    ->count(),


        ];







        return new DashboardMetricsResource([


            'trips' =>

                $trips,


            'alerts' =>

                $alerts,


            'monitoring' =>

                $monitoring,


            'notifications' =>

                $notifications,


        ]);


    }









    /*
    |--------------------------------------------------------------------------
    | Fleet KPI
    |--------------------------------------------------------------------------
    */


    public function kpi()
    {


        return response()->json([


            'active_trips' =>

                Trip::where(
                    'status',
                    Trip::STATUS_STARTED
                )->count(),





            'finished_today' =>

                Trip::where(
                    'status',
                    Trip::STATUS_FINISHED
                )
                ->whereDate(
                    'finished_at',
                    Carbon::today()
                )
                ->count(),





            'open_alerts' =>

                Alert::whereNull(
                    'resolved_at'
                )->count(),





            'gps_lost' =>

                Alert::where(
                    'type',
                    'gps_lost'
                )
                ->whereNull(
                    'resolved_at'
                )
                ->count(),





            'eta_delay' =>

                Alert::where(
                    'type',
                    'eta_delay'
                )
                ->whereNull(
                    'resolved_at'
                )
                ->count(),





            'vehicle_idle' =>

                Alert::where(
                    'type',
                    'vehicle_idle'
                )
                ->whereNull(
                    'resolved_at'
                )
                ->count(),





            'critical_alerts' =>

                Alert::whereNull(
                    'resolved_at'
                )
                ->where(
                    'severity',
                    'critical'
                )
                ->count(),


        ]);


    }


}