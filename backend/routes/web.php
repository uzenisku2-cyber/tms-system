<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VehiclePositionController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
|
*/


Route::get('/', function () {
    return view('welcome');
});



/*
|--------------------------------------------------------------------------
| Realtime tracking test
|--------------------------------------------------------------------------
*/

if (app()->environment('local')) {
    Route::get('/realtime-test', function () {
        return view('realtime-test');
    });
}



/*
|--------------------------------------------------------------------------
| Vehicle position history API
|--------------------------------------------------------------------------
*/

Route::get(
    '/trips/{tripId}/positions',
    [VehiclePositionController::class, 'index']
);