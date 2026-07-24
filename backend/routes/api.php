<?php

declare(strict_types=1);

use App\Http\Controllers\Api\GpsController;
use App\Http\Controllers\VehicleController as RealtimeVehicleController;
use App\Http\Controllers\VehiclePositionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Versioned module API gateway
|--------------------------------------------------------------------------
|
| ModuleServiceProvider loads module migrations only. Module route files
| are therefore registered explicitly under the common /api/v1 prefix.
|
*/

Route::prefix('v1')->group(function (): void {
    $moduleRouteFiles = [
        app_path('Modules/Identity/Routes/api.php'),
        app_path('Modules/Drivers/Routes/api.php'),
        app_path('Modules/Fleet/Routes/api.php'),
        app_path('Modules/Trips/Routes/api.php'),
        app_path('Modules/Notifications/Routes/api.php'),
    ];

    foreach ($moduleRouteFiles as $moduleRouteFile) {
        if (!is_file($moduleRouteFile)) {
            throw new \RuntimeException(
                "Module API route file not found: {$moduleRouteFile}"
            );
        }

        require $moduleRouteFile;
    }
});

/*
|--------------------------------------------------------------------------
| Legacy realtime prototype API
|--------------------------------------------------------------------------
|
| These endpoints temporarily retain their original URLs. Authentication,
| ownership and canonical Fleet integration are handled in the next unit.
|
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post(
        '/gps/update',
        [GpsController::class, 'update']
    );

    Route::get(
        '/vehicles',
        [RealtimeVehicleController::class, 'index']
    );

    Route::get(
        '/vehicles/{vehicle}/positions',
        [VehiclePositionController::class, 'index']
    );
});
