<?php

use App\Modules\Fleet\Controllers\VehicleController;
use App\Modules\Fleet\Controllers\VehicleCostAllocationBillingDocumentHandoffController;
use App\Modules\Fleet\Controllers\VehicleCostAllocationController;
use App\Modules\Fleet\Controllers\VehicleCostAllocationDepositOffsetController;
use App\Modules\Fleet\Controllers\VehicleCostAllocationFinancialHandoffController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('vehicles', VehicleController::class);
    Route::post('vehicle-cost-allocations', [VehicleCostAllocationController::class, 'store'])->name('vehicle-cost-allocations.store');
    Route::get('vehicle-cost-allocations/{allocationUid}', [VehicleCostAllocationController::class, 'show'])->name('vehicle-cost-allocations.show');
    Route::post('vehicle-cost-allocations/{allocationUid}/approve', [VehicleCostAllocationController::class, 'approve'])->name('vehicle-cost-allocations.approve');
    Route::post('vehicle-cost-allocations/{allocationUid}/financial-handoff', [VehicleCostAllocationFinancialHandoffController::class, 'prepare'])->name('vehicle-cost-allocations.financial-handoff.prepare');
    Route::get('vehicle-cost-allocations/{allocationUid}/financial-handoff', [VehicleCostAllocationFinancialHandoffController::class, 'show'])->name('vehicle-cost-allocations.financial-handoff.show');
    Route::post('vehicle-cost-allocation-financial-handoff-instructions/{instructionPublicId}/billing-document', [VehicleCostAllocationBillingDocumentHandoffController::class, 'execute'])->name('vehicle-cost-allocation-financial-handoff-instructions.billing-document.execute');
    Route::post('vehicle-cost-allocation-financial-handoff-instructions/{instructionPublicId}/deposit-offset', [VehicleCostAllocationDepositOffsetController::class, 'acknowledge'])->name('vehicle-cost-allocation-financial-handoff-instructions.deposit-offset.acknowledge');
});
