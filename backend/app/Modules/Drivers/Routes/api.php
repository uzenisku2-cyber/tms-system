<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Drivers\Controllers\DriverController;


Route::middleware('auth:sanctum')
    ->group(function () {

        Route::get(
            '/drivers',
            [DriverController::class, 'index']
        );

        Route::post(
            '/drivers',
            [DriverController::class, 'store']
        );

        Route::get(
            '/drivers/{driver}',
            [DriverController::class, 'show']
        );

        Route::patch(
            '/drivers/{driver}',
            [DriverController::class, 'update']
        );

        Route::delete(
            '/drivers/{driver}',
            [DriverController::class, 'destroy']
        );

    });