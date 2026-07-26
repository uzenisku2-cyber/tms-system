<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Fleet\Controllers\VehicleController;

/*
|--------------------------------------------------------------------------
| FLEET MODULE
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::apiResource('vehicles', VehicleController::class);

});