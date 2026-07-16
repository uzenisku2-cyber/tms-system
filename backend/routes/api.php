<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GpsController;
use App\Http\Controllers\VehiclePositionController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them
| will be assigned to the "api" middleware group.
|
*/


Route::post(
    '/gps/update',
    [GpsController::class, 'update']
);
Route::get(
    '/vehicles/{vehicle}/positions',
    [VehiclePositionController::class, 'index']
);