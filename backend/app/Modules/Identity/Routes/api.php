<?php

use App\Modules\Identity\Controllers\AuthController;
use App\Modules\Identity\Controllers\OrganizationPeopleController;
use Illuminate\Support\Facades\Route;

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
        Route::get('/capabilities', [AuthController::class, 'capabilities'])
            ->middleware('organization');
    });

});
Route::middleware(['auth:sanctum', 'organization', 'perm:people.manage'])
    ->prefix('people')->group(function (): void {
        Route::get('/', [OrganizationPeopleController::class, 'index']);
        Route::post('/', [OrganizationPeopleController::class, 'store']);
        Route::patch('/{user}/roles', [OrganizationPeopleController::class, 'updateRoles'])
            ->whereNumber('user');
    });

Route::get('people/carriers/{organization}', [OrganizationPeopleController::class, 'indexCarrier'])
    ->whereNumber('organization')
    ->middleware(['auth:sanctum', 'organization', 'perm:users.manage']);

Route::post('people/carriers/{organization}',
    [OrganizationPeopleController::class, 'storeCarrier'])
    ->whereNumber('organization')
    ->middleware(['auth:sanctum', 'organization', 'perm:users.manage']);

Route::patch('people/carriers/{organization}/{user}/roles',
    [OrganizationPeopleController::class, 'updateCarrierRoles'])
    ->whereNumber('organization')->whereNumber('user')
    ->middleware(['auth:sanctum', 'organization', 'perm:users.manage']);
