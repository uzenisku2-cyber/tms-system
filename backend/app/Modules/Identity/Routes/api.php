<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Identity\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| AUTH MODULE (Identity)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {

    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

});