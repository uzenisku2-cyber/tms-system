<?php

declare(strict_types=1);
use App\Modules\Fuel\Controllers\FuelCardController;
use App\Modules\Fuel\Controllers\FuelTransactionImportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'organization'])->prefix('fuel-cards')->name('fuel-cards.')->group(function (): void {
    Route::middleware('perm:compensation.view')->group(function (): void {
        Route::get('/', [FuelCardController::class, 'index'])->name('index');
        Route::get('/{fuelCard}', [FuelCardController::class, 'show'])->whereUuid('fuelCard')->name('show');
    });
    Route::middleware('perm:users.manage')->group(function (): void {
        Route::post('/', [FuelCardController::class, 'store'])->name('store');
        Route::patch('/{fuelCard}/status', [FuelCardController::class, 'changeStatus'])->whereUuid('fuelCard')->name('status');
        Route::post('/{fuelCard}/assignments', [FuelCardController::class, 'assign'])->whereUuid('fuelCard')->name('assignments.store');
        Route::post('/{fuelCard}/assignments/{assignment}/end', [FuelCardController::class, 'endAssignment'])->whereUuid('fuelCard')->whereUuid('assignment')->name('assignments.end');
        Route::post('/{fuelCard}/settlement-policies', [FuelCardController::class, 'storePolicy'])->whereUuid('fuelCard')->name('settlement-policies.store');
    });
});

Route::middleware(['auth:sanctum', 'organization'])->prefix('fuel-imports')->name('fuel-imports.')->group(function (): void {
    Route::middleware('perm:compensation.view')->group(function (): void {
        Route::get('/', [FuelTransactionImportController::class, 'index'])->name('index');
        Route::get('/{batch}', [FuelTransactionImportController::class, 'show'])->whereUuid('batch')->name('show');
        Route::get('/{batch}/rows/{sourceRow}', [FuelTransactionImportController::class, 'row'])->whereUuid('batch')->whereNumber('sourceRow')->name('rows.show');
    });
    Route::middleware('perm:users.manage')->group(function (): void {
        Route::post('/', [FuelTransactionImportController::class, 'store'])->name('store');
        Route::post('/{batch}/rows/{sourceRow}/corrections', [FuelTransactionImportController::class, 'correct'])->whereUuid('batch')->whereNumber('sourceRow')->name('rows.corrections.store');
    });
    Route::middleware('perm:compensation.manage')->group(function (): void {
        Route::post('/{batch}/rows/{sourceRow}/finalization', [FuelTransactionImportController::class, 'finalize'])->whereUuid('batch')->whereNumber('sourceRow')->name('rows.finalization.store');
    });
});
