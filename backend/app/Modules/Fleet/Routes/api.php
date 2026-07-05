<?php

declare(strict_types=1);

use App\Modules\Fleet\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::prefix('fleet')->group(function (): void {
    Route::apiResource(
        'vehicles',
        VehicleController::class
    );
});
