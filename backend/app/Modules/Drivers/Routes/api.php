<?php

use App\Modules\Drivers\Controllers\DriverAvailabilityCalendarController;
use App\Modules\Drivers\Controllers\DriverController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->group(function () {

        Route::get('/driver-availability-calendar', [DriverAvailabilityCalendarController::class, 'index'])->middleware('organization');
        Route::post('/driver-availability-calendar', [DriverAvailabilityCalendarController::class, 'store'])->middleware('organization');
        Route::post('/driver-availability-calendar/{day}/decision', [DriverAvailabilityCalendarController::class, 'decide'])->whereNumber('day')->middleware('organization');

        Route::get(
            '/drivers',
            [DriverController::class, 'index']
        );

        Route::post('/drivers', static fn () => response()->json([
            'message' => 'Profil řidiče se vytváří prostřednictvím pozvánky v Lidé a přístupy.',
        ], 410));

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
