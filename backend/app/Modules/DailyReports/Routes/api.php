<?php

declare(strict_types=1);

use App\Modules\DailyReports\Controllers\DailyReportController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'organization',
])
    ->prefix('daily-reports')
    ->name('daily-reports.')
    ->group(function (): void {
        Route::middleware(
            'perm:daily-reports.view',
        )->group(function (): void {
            Route::get(
                '/',
                [DailyReportController::class, 'index'],
            )->name('index');

            Route::get(
                '/{dailyReport}/versions',
                [DailyReportController::class, 'versions'],
            )
                ->whereUuid('dailyReport')
                ->name('versions');

            Route::get(
                '/{dailyReport}/events',
                [DailyReportController::class, 'events'],
            )
                ->whereUuid('dailyReport')
                ->name('events');
            Route::get(
                '/{dailyReport}',
                [DailyReportController::class, 'show'],
            )
                ->whereUuid('dailyReport')
                ->name('show');
        });

        Route::post(
            '/',
            [DailyReportController::class, 'store'],
        )->name('store');

        Route::patch(
            '/{dailyReport}',
            [DailyReportController::class, 'update'],
        )
            ->whereUuid('dailyReport')
            ->name('update');

        Route::post(
            '/{dailyReport}/submit',
            [DailyReportController::class, 'submit'],
        )
            ->whereUuid('dailyReport')
            ->name('submit');

        Route::post(
            '/{dailyReport}/review',
            [DailyReportController::class, 'review'],
        )
            ->middleware('perm:daily-reports.review')
            ->whereUuid('dailyReport')
            ->name('review');

        Route::post(
            '/{dailyReport}/request-correction',
            [DailyReportController::class, 'requestCorrection'],
        )
            ->middleware(
                'perm:daily-reports.request-correction',
            )
            ->whereUuid('dailyReport')
            ->name('request-correction');

        Route::post(
            '/{dailyReport}/correct',
            [DailyReportController::class, 'correct'],
        )
            ->whereUuid('dailyReport')
            ->name('correct');

        Route::post(
            '/{dailyReport}/resubmit',
            [DailyReportController::class, 'resubmit'],
        )
            ->whereUuid('dailyReport')
            ->name('resubmit');

        Route::post(
            '/{dailyReport}/approve',
            [DailyReportController::class, 'approve'],
        )
            ->middleware('perm:daily-reports.approve')
            ->whereUuid('dailyReport')
            ->name('approve');

        Route::post(
            '/{dailyReport}/close',
            [DailyReportController::class, 'close'],
        )
            ->middleware('perm:daily-reports.close')
            ->whereUuid('dailyReport')
            ->name('close');
    });
