<?php

use App\Modules\Fleet\Controllers\VehicleController;
use App\Modules\Fleet\Controllers\VehicleCostAllocationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| FLEET MODULE
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('vehicles', VehicleController::class);
    Route::post('vehicle-cost-allocations', [VehicleCostAllocationController::class, 'store'])->name('vehicle-cost-allocations.store');
    Route::get('vehicle-cost-allocations/{allocationUid}', [VehicleCostAllocationController::class, 'show'])->name('vehicle-cost-allocations.show');
    Route::post('vehicle-cost-allocations/{allocationUid}/approve', [VehicleCostAllocationController::class, 'approve'])->name('vehicle-cost-allocations.approve');
});
