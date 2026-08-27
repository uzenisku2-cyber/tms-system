<?php

use App\Http\Controllers\VehiclePositionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MVP / Pilot Launch UI
|--------------------------------------------------------------------------
|
| The pilot shell intentionally stays inside Laravel for the fastest
| possible path from the existing API foundation to a usable browser UI.
| Authentication and business actions continue to use /api/v1 endpoints.
|
*/

Route::view('/', 'mvp.app')->name('mvp.home');
Route::view('/login', 'mvp.app')->name('mvp.login');
Route::view('/app', 'mvp.app')->name('mvp.app');

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

// S020-03A CARRIER ADMIN PAGE
Route::view(
    '/carriers',
    'mvp.carriers',
)->name('mvp.carriers');

// S020-04B DAILY REPORT SETTINGS PAGE
Route::view(
    '/daily-report-settings',
    'mvp.daily-report-settings',
)->name('mvp.daily-report-settings');

// S020-04F2A2 SETTINGS CATALOGS FOUNDATION
Route::view('/settings', 'mvp.settings')->name('mvp.settings');
Route::view('/settings/fuel-imports', 'mvp.fuel-imports')->name('mvp.settings.fuel-imports');
Route::view('/settings/catalogs', 'mvp.settings-catalogs')->name('mvp.settings.catalogs');
Route::view('/settings/catalogs/routes', 'mvp.settings-route-catalog-crud')->name('mvp.settings.catalogs.routes');
Route::view('/settings/catalogs/route-characters', 'mvp.settings-route-characters')->name('mvp.settings.catalogs.route-characters');
Route::view('/settings/catalogs/operational-reasons', 'mvp.settings-operational-reasons')->name('mvp.settings.catalogs.operational-reasons');
Route::view('/settings/routes', 'mvp.route-settings')->name('mvp.settings.routes');
