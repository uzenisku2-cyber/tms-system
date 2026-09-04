<?php

declare(strict_types=1);

use App\Modules\Fuel\Controllers\FuelCardController;
use App\Modules\Fuel\Controllers\FuelSurchargeController;
use App\Modules\Fuel\Controllers\FuelTransactionController;
use App\Modules\Fuel\Controllers\FuelTransactionDriverAttributionController;
use App\Modules\Fuel\Controllers\FuelTransactionImportController;
use App\Modules\Fuel\Controllers\FuelTransactionReconciliationController;
use App\Modules\Fuel\Controllers\FuelTransactionSettlementApplicationController;
use App\Modules\Fuel\Controllers\FuelTransactionSettlementEligibilityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'organization'])
    ->prefix('fuel-cards')
    ->name('fuel-cards.')
    ->group(function (): void {
        Route::middleware('perm:compensation.view')->group(function (): void {
            Route::get('/', [FuelCardController::class, 'index'])->name('index');
            Route::get('/{fuelCard}', [FuelCardController::class, 'show'])
                ->whereUuid('fuelCard')
                ->name('show');
        });
        Route::middleware('perm:users.manage')->group(function (): void {
            Route::post('/', [FuelCardController::class, 'store'])->name('store');
            Route::patch('/{fuelCard}', [FuelCardController::class, 'update'])->name('update');
            Route::patch('/{fuelCard}/status', [FuelCardController::class, 'changeStatus'])
                ->whereUuid('fuelCard')
                ->name('status');
            Route::post('/{fuelCard}/assignments', [FuelCardController::class, 'assign'])
                ->whereUuid('fuelCard')
                ->name('assignments.store');
            Route::post('/{fuelCard}/assignments/{assignment}/end', [FuelCardController::class, 'endAssignment'])
                ->whereUuid('fuelCard')
                ->whereUuid('assignment')
                ->name('assignments.end');
            Route::post('/{fuelCard}/settlement-policies', [FuelCardController::class, 'storePolicy'])
                ->whereUuid('fuelCard')
                ->name('settlement-policies.store');
        });
    });

Route::middleware(['auth:sanctum', 'organization'])
    ->prefix('fuel-surcharges')
    ->name('fuel-surcharges.')
    ->group(function (): void {
        Route::get('/mine', [FuelSurchargeController::class, 'mine'])
            ->name('mine');

        Route::middleware('perm:compensation.manage')->group(function (): void {
            Route::get('/', [FuelSurchargeController::class, 'index'])
                ->name('index');
            Route::post('/', [FuelSurchargeController::class, 'store'])
                ->name('store');
            Route::get('/{fuelSurcharge}', [FuelSurchargeController::class, 'show'])
                ->whereUuid('fuelSurcharge')
                ->name('show');
        });
    });

Route::middleware(['auth:sanctum', 'organization'])
    ->prefix('fuel-transactions')
    ->name('fuel-transactions.')
    ->group(function (): void {
        Route::middleware('perm:compensation.view')->group(function (): void {
            Route::get('/export', [FuelTransactionController::class, 'export'])->name('export');
            Route::get('/export-history', [FuelTransactionController::class, 'exportHistory'])->name('export-history');
            Route::get('/overview', [FuelTransactionController::class, 'overview'])->name('overview');
            Route::get('/', [FuelTransactionController::class, 'index'])->name('index');
        });
        Route::middleware('perm:users.manage')->group(function (): void {
            Route::get('/{fuelTransaction}/driver-attribution', [FuelTransactionDriverAttributionController::class, 'show'])->whereUuid('fuelTransaction')->name('driver-attribution.show');
            Route::get('/{fuelTransaction}/eligible-drivers', [FuelTransactionDriverAttributionController::class, 'eligibleDrivers'])->whereUuid('fuelTransaction')->name('eligible-drivers.index');
            Route::post('/{fuelTransaction}/driver-attributions', [FuelTransactionDriverAttributionController::class, 'store'])->whereUuid('fuelTransaction')->name('driver-attributions.store');
        });
    });
Route::middleware(['auth:sanctum', 'organization'])
    ->prefix('fuel-transactions')
    ->name('fuel-transactions.')
    ->group(function (): void {
        Route::middleware('perm:compensation.view')->group(function (): void {
            Route::get('/{fuelTransaction}/reconciliation', [FuelTransactionReconciliationController::class, 'show'])->whereUuid('fuelTransaction')->name('reconciliation.show');
            Route::get('/{fuelTransaction}/settlement-eligibility', [FuelTransactionSettlementEligibilityController::class, 'show'])->whereUuid('fuelTransaction')->name('settlement-eligibility.show');
            Route::get('/{fuelTransaction}/settlement-application', [FuelTransactionSettlementApplicationController::class, 'show'])->whereUuid('fuelTransaction')->name('settlement-application.show');
        });
        Route::middleware('perm:compensation.manage')->group(function (): void {
            Route::post('/{fuelTransaction}/reconciliation/evaluate', [FuelTransactionReconciliationController::class, 'evaluate'])->whereUuid('fuelTransaction')->name('reconciliation.evaluate');
            Route::post('/{fuelTransaction}/reconciliation/decisions', [FuelTransactionReconciliationController::class, 'decide'])->whereUuid('fuelTransaction')->name('reconciliation.decisions.store');
            Route::post('/{fuelTransaction}/settlement-eligibility/evaluate', [FuelTransactionSettlementEligibilityController::class, 'evaluate'])->whereUuid('fuelTransaction')->name('settlement-eligibility.evaluate');
            Route::post('/{fuelTransaction}/settlement-application', [FuelTransactionSettlementApplicationController::class, 'apply'])->whereUuid('fuelTransaction')->name('settlement-application.apply');
            Route::post('/{fuelTransaction}/settlement-application/financial-calculation', [FuelTransactionSettlementApplicationController::class, 'attachFinancialCalculation'])->whereUuid('fuelTransaction')->name('settlement-application.financial-calculation.attach');
            Route::post('/{fuelTransaction}/settlement-application/reverse', [FuelTransactionSettlementApplicationController::class, 'reverse'])->whereUuid('fuelTransaction')->name('settlement-application.reverse');
        });
    });
Route::middleware(['auth:sanctum', 'organization'])
    ->prefix('fuel-imports')
    ->name('fuel-imports.')
    ->group(function (): void {
        Route::middleware('perm:compensation.view')->group(function (): void {
            Route::get('/', [FuelTransactionImportController::class, 'index'])->name('index');
            Route::get('/{batch}', [FuelTransactionImportController::class, 'show'])
                ->whereUuid('batch')
                ->name('show');
            Route::get('/{batch}/rows/{sourceRow}', [FuelTransactionImportController::class, 'row'])
                ->whereUuid('batch')
                ->whereNumber('sourceRow')
                ->name('rows.show');
        });
        Route::middleware('perm:users.manage')->group(function (): void {
            Route::post('/', [FuelTransactionImportController::class, 'store'])->name('store');
            Route::post('/{batch}/rows/{sourceRow}/corrections', [FuelTransactionImportController::class, 'correct'])
                ->whereUuid('batch')
                ->whereNumber('sourceRow')
                ->name('rows.corrections.store');
        });
        Route::middleware('perm:compensation.manage')->group(function (): void {
            Route::post('/{batch}/rows/{sourceRow}/finalization', [FuelTransactionImportController::class, 'finalize'])
                ->whereUuid('batch')
                ->whereNumber('sourceRow')
                ->name('rows.finalization.store');
        });
    });
