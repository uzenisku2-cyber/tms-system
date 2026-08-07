<?php

declare(strict_types=1);

use App\Modules\Pricing\Controllers\FinancialCalculationController;
use App\Modules\Pricing\Controllers\PriceListController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'organization',
])
    ->prefix('price-lists')
    ->name('price-lists.')
    ->group(function (): void {
        Route::post(
            '/',
            [PriceListController::class, 'store'],
        )
            ->middleware('perm:pricing.manage')
            ->name('store');

        Route::post(
            '/{priceList}/versions',
            [PriceListController::class, 'storeVersion'],
        )
            ->whereUuid('priceList')
            ->middleware('perm:pricing.manage')
            ->name('versions.store');

        Route::post(
            '/{priceList}/versions/{version}/approve',
            [PriceListController::class, 'approveVersion'],
        )
            ->whereUuid('priceList')
            ->whereNumber('version')
            ->middleware('perm:pricing.manage')
            ->name('versions.approve');

        Route::post(
            '/{priceList}/versions/{version}/activate',
            [PriceListController::class, 'activateVersion'],
        )
            ->whereUuid('priceList')
            ->whereNumber('version')
            ->middleware('perm:pricing.manage')
            ->name('versions.activate');

        Route::post(
            '/{priceList}/versions/{version}/expire',
            [PriceListController::class, 'expireVersion'],
        )
            ->whereUuid('priceList')
            ->whereNumber('version')
            ->middleware('perm:pricing.manage')
            ->name('versions.expire');

        Route::put(
            '/{priceList}/versions/{version}',
            [PriceListController::class, 'updateVersion'],
        )
            ->whereUuid('priceList')
            ->whereNumber('version')
            ->middleware('perm:pricing.manage')
            ->name('versions.update');

        Route::middleware('perm:pricing.view')
            ->group(function (): void {
                Route::get(
                    '/',
                    [PriceListController::class, 'index'],
                )->name('index');

                Route::get(
                    '/{priceList}/versions',
                    [PriceListController::class, 'versions'],
                )
                    ->whereUuid('priceList')
                    ->name('versions.index');

                Route::get(
                    '/{priceList}/versions/{version}',
                    [PriceListController::class, 'version'],
                )
                    ->whereUuid('priceList')
                    ->whereNumber('version')
                    ->name('versions.show');

                Route::get(
                    '/{priceList}',
                    [PriceListController::class, 'show'],
                )
                    ->whereUuid('priceList')
                    ->name('show');
            });
    });

Route::middleware([
    'auth:sanctum',
    'organization',
    'perm:compensation.manage',
])
    ->prefix('financial-calculations')
    ->name('financial-calculations.')
    ->group(function (): void {
        Route::post(
            '/',
            [FinancialCalculationController::class, 'store'],
        )->name('store');

        Route::post(
            '/{financialCalculation}/recalculate',
            [FinancialCalculationController::class, 'recalculate'],
        )
            ->whereUuid('financialCalculation')
            ->name('recalculate');
        Route::post(
            '/{financialCalculation}/review',
            [FinancialCalculationController::class, 'review'],
        )
            ->whereUuid('financialCalculation')
            ->name('review');

        Route::post(
            '/{financialCalculation}/approve',
            [FinancialCalculationController::class, 'approve'],
        )
            ->whereUuid('financialCalculation')
            ->name('approve');

        Route::post(
            '/{financialCalculation}/close',
            [FinancialCalculationController::class, 'close'],
        )
            ->whereUuid('financialCalculation')
            ->name('close');

        Route::post(
            '/{financialCalculation}/cancel',
            [FinancialCalculationController::class, 'cancel'],
        )
            ->whereUuid('financialCalculation')
            ->name('cancel');
    });

Route::middleware([
    'auth:sanctum',
    'organization',
    'perm:compensation.view',
])
    ->prefix('financial-calculations')
    ->name('financial-calculations.')
    ->group(function (): void {
        Route::get(
            '/',
            [FinancialCalculationController::class, 'index'],
        )->name('index');

        Route::get(
            '/{financialCalculation}/events',
            [FinancialCalculationController::class, 'events'],
        )
            ->whereUuid('financialCalculation')
            ->name('events.index');

        Route::get(
            '/{financialCalculation}',
            [FinancialCalculationController::class, 'show'],
        )
            ->whereUuid('financialCalculation')
            ->name('show');
    });
