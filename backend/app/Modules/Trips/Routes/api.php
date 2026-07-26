<?php

use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Controllers
|--------------------------------------------------------------------------
*/


use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AlertController;

use App\Http\Controllers\TripPodController;
use App\Http\Controllers\TripTrackingController;
use App\Http\Controllers\TripLiveController;
use App\Http\Controllers\TripProgressController;
use App\Http\Controllers\TripEtaController;
use App\Http\Controllers\DriverTripController;
use App\Http\Controllers\TripRealtimeController;

use App\Modules\Trips\Controllers\TripController;
use App\Modules\Trips\Controllers\TripAssignmentController;
use App\Modules\Trips\Controllers\TripTimelineController;

use App\Modules\Drivers\Controllers\DriverAvailabilityController;
use App\Modules\Fleet\Controllers\VehicleAvailabilityController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/


Route::middleware('auth:sanctum')
    ->group(function () {


        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */


        Route::get(
            '/dashboard',
            [
                DashboardController::class,
                'index'
            ]
        );


        Route::get(
            '/dashboard/metrics',
            [
                DashboardController::class,
                'metrics'
            ]
        );


        Route::get(
            '/dashboard/kpi',
            [
                DashboardController::class,
                'kpi'
            ]
        );

/*
|--------------------------------------------------------------------------
| Realtime Projection
|--------------------------------------------------------------------------
*/

Route::get(
    '/trips/{trip}/realtime',
    [
        TripRealtimeController::class,
        'show'
    ]
);



        /*
        |--------------------------------------------------------------------------
        | Reports
        |--------------------------------------------------------------------------
        */


        Route::get(
            '/reports/summary',
            [
                ReportController::class,
                'summary'
            ]
        );


        Route::get(
            '/reports/drivers',
            [
                ReportController::class,
                'drivers'
            ]
        );


        Route::get(
            '/reports/vehicles',
            [
                ReportController::class,
                'vehicles'
            ]
        );


        Route::get(
            '/reports/drivers/{driver}',
            [
                ReportController::class,
                'driver'
            ]
        );


        Route::get(
            '/reports/vehicles/{vehicle}',
            [
                ReportController::class,
                'vehicle'
            ]
        );





        /*
        |--------------------------------------------------------------------------
        | Alerts
        |--------------------------------------------------------------------------
        */


        Route::get(
            '/alerts',
            [
                AlertController::class,
                'index'
            ]
        );


        Route::get(
            '/alerts/open',
            [
                AlertController::class,
                'open'
            ]
        );


        Route::get(
            '/alerts/history',
            [
                AlertController::class,
                'history'
            ]
        );


        Route::get(
            '/alerts/unread',
            [
                AlertController::class,
                'unread'
            ]
        );


        Route::get(
            '/alerts/summary',
            [
                AlertController::class,
                'summary'
            ]
        );


        Route::get(
            '/alerts/{alert}',
            [
                AlertController::class,
                'show'
            ]
        );


        Route::patch(
            '/alerts/{alert}/read',
            [
                AlertController::class,
                'read'
            ]
        );


        Route::patch(
            '/alerts/read-all',
            [
                AlertController::class,
                'readAll'
            ]
        );


        Route::patch(
            '/alerts/{alert}/resolve',
            [
                AlertController::class,
                'resolve'
            ]
        );





        /*
        |--------------------------------------------------------------------------
        | Trips CRUD
        |--------------------------------------------------------------------------
        */


        Route::get(
            '/trips',
            [
                TripController::class,
                'index'
            ]
        );


        Route::post(
            '/trips',
            [
                TripController::class,
                'store'
            ]
        );


        Route::get(
            '/trips/{trip}',
            [
                TripController::class,
                'show'
            ]
        );


        Route::patch(
            '/trips/{trip}',
            [
                TripController::class,
                'update'
            ]
        );


        Route::delete(
            '/trips/{trip}',
            [
                TripController::class,
                'destroy'
            ]
        );





        /*
        |--------------------------------------------------------------------------
        | Assignment
        |--------------------------------------------------------------------------
        */


        Route::post(
            '/trips/{trip}/assign',
            [
                TripAssignmentController::class,
                'assign'
            ]
        );

/*
|--------------------------------------------------------------------------
| Driver trip actions
|--------------------------------------------------------------------------
*/


Route::post(
    '/trips/{trip}/start',
    [
        DriverTripController::class,
        'start'
    ]
);


Route::post(
    '/trips/{trip}/finish',
    [
        DriverTripController::class,
        'finish'
    ]
);



        /*
        |--------------------------------------------------------------------------
        | Timeline
        |--------------------------------------------------------------------------
        */


        Route::get(
            '/trips/{trip}/timeline',
            [
                TripTimelineController::class,
                'index'
            ]
        );





        /*
        |--------------------------------------------------------------------------
        | POD
        |--------------------------------------------------------------------------
        */


        Route::post(
            '/trips/{trip}/pod',
            [
                TripPodController::class,
                'store'
            ]
        );





        /*
        |--------------------------------------------------------------------------
        | Tracking
        |--------------------------------------------------------------------------
        */


        Route::post(
            '/trips/{trip}/locations',
            [
                TripTrackingController::class,
                'store'
            ]
        );


        Route::get(
            '/trips/{trip}/tracking',
            [
                TripTrackingController::class,
                'index'
            ]
        );


        Route::get(
            '/trips/active/live',
            [
                TripTrackingController::class,
                'activeLive'
            ]
        );





        /*
        |--------------------------------------------------------------------------
        | Live / Progress / ETA
        |--------------------------------------------------------------------------
        */


        Route::get(
            '/trips/{trip}/live',
            [
                TripLiveController::class,
                'show'
            ]
        );


        Route::get(
            '/trips/{trip}/progress',
            [
                TripProgressController::class,
                'show'
            ]
        );


        Route::get(
            '/trips/{trip}/eta',
            [
                TripEtaController::class,
                'show'
            ]
        );





        /*
        |--------------------------------------------------------------------------
        | Driver availability
        |--------------------------------------------------------------------------
        */


        Route::get(
            '/drivers/available',
            [
                DriverAvailabilityController::class,
                'index'
            ]
        );





        /*
        |--------------------------------------------------------------------------
        | Vehicle availability
        |--------------------------------------------------------------------------
        */


        Route::get(
            '/vehicles/available',
            [
                VehicleAvailabilityController::class,
                'index'
            ]
        );


    });