<?php

declare(strict_types=1);

use App\Modules\DailyReports\Controllers\DailyReportController;
use App\Modules\DailyReports\Controllers\DailyReportPerformancePolicyController;
use App\Modules\DailyReports\Controllers\DriverQualityProfileController;
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
                '/performance-overview',
                [
                    DailyReportController::class,
                    'performanceOverview',
                ],
            )->name('performance-overview');

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
        Route::delete(
            '/{dailyReport}',
            [DailyReportController::class, 'destroy'],
        )
            ->whereUuid('dailyReport')
            ->name('destroy');

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

/*
|--------------------------------------------------------------------------
| S020-04E3D3 OPERATIONAL PERFORMANCE POLICY ROUTES
|--------------------------------------------------------------------------
|
| Operational tolerance configuration is intentionally separate from
| financial price-list rules. Read access follows daily-reports.view.
| Configuration writes require daily-reports.review.
|
*/

Route::middleware([
    'auth:sanctum',
    'organization',
])
    ->prefix('daily-reports/performance-policies')
    ->name('daily-reports.performance-policies.')
    ->group(function (): void {
        Route::middleware(
            'perm:daily-reports.view',
        )->group(function (): void {
            Route::get(
                '/',
                [
                    DailyReportPerformancePolicyController::class,
                    'index',
                ],
            )->name('index');

            Route::get(
                '/effective',
                [
                    DailyReportPerformancePolicyController::class,
                    'effective',
                ],
            )->name('effective');
        });

        Route::middleware(
            'perm:daily-reports.review',
        )->group(function (): void {
            Route::put(
                '/organization',
                [
                    DailyReportPerformancePolicyController::class,
                    'updateOrganization',
                ],
            )->name('organization.update');

            Route::put(
                '/routes/{routeNumber}',
                [
                    DailyReportPerformancePolicyController::class,
                    'updateRoute',
                ],
            )->name('route.update');

            Route::delete(
                '/routes/{routeNumber}',
                [
                    DailyReportPerformancePolicyController::class,
                    'deleteRoute',
                ],
            )->name('route.delete');
        });
    });

/*
|--------------------------------------------------------------------------
| S027 DRIVER QUALITY PROFILE ADMINISTRATION ROUTES
|--------------------------------------------------------------------------
|
| Quality profiles select canonical parcel metrics for partial quality.
| They remain independent from operational policies and financial rules.
|
*/

Route::middleware([
    'auth:sanctum',
    'organization',
])
    ->prefix('daily-reports/quality-profiles')
    ->name('daily-reports.quality-profiles.')
    ->group(function (): void {
        Route::middleware(
            'perm:daily-reports.view',
        )->group(function (): void {
            Route::get(
                '/',
                [DriverQualityProfileController::class, 'index'],
            )->name('index');

            Route::get(
                '/targets',
                [
                    DriverQualityProfileController::class,
                    'bindingTargets',
                ],
            )->name('targets');

            Route::get(
                '/bindings',
                [DriverQualityProfileController::class, 'bindings'],
            )->name('bindings');

            Route::get(
                '/effective',
                [DriverQualityProfileController::class, 'effective'],
            )->name('effective');

            Route::get(
                '/{qualityProfile}',
                [DriverQualityProfileController::class, 'show'],
            )
                ->whereUuid('qualityProfile')
                ->name('show');
        });

        Route::middleware(
            'perm:daily-reports.review',
        )->group(function (): void {
            Route::post(
                '/',
                [DriverQualityProfileController::class, 'store'],
            )->name('store');

            Route::post(
                '/{qualityProfile}/versions',
                [
                    DriverQualityProfileController::class,
                    'storeVersion',
                ],
            )
                ->whereUuid('qualityProfile')
                ->name('versions.store');

            Route::put(
                '/{qualityProfile}/versions/{version}',
                [
                    DriverQualityProfileController::class,
                    'updateVersion',
                ],
            )
                ->whereUuid('qualityProfile')
                ->whereNumber('version')
                ->name('versions.update');

            Route::post(
                '/{qualityProfile}/versions/{version}/activate',
                [
                    DriverQualityProfileController::class,
                    'activateVersion',
                ],
            )
                ->whereUuid('qualityProfile')
                ->whereNumber('version')
                ->name('versions.activate');

            Route::put(
                '/bindings/organization',
                [
                    DriverQualityProfileController::class,
                    'bindOrganization',
                ],
            )->name('bindings.organization');

            Route::put(
                '/bindings/carrier-relationships/{relationship}',
                [
                    DriverQualityProfileController::class,
                    'bindCarrier',
                ],
            )
                ->whereNumber('relationship')
                ->name('bindings.carrier');

            Route::put(
                '/bindings/driver-assignments/{assignment}',
                [
                    DriverQualityProfileController::class,
                    'bindDriver',
                ],
            )
                ->whereNumber('assignment')
                ->name('bindings.driver');

            Route::delete(
                '/bindings/organization',
                [
                    DriverQualityProfileController::class,
                    'endOrganizationBinding',
                ],
            )->name('bindings.organization.end');

            Route::delete(
                '/bindings/carrier-relationships/{relationship}',
                [
                    DriverQualityProfileController::class,
                    'endCarrierBinding',
                ],
            )
                ->whereNumber('relationship')
                ->name('bindings.carrier.end');

            Route::delete(
                '/bindings/driver-assignments/{assignment}',
                [
                    DriverQualityProfileController::class,
                    'endDriverBinding',
                ],
            )
                ->whereNumber('assignment')
                ->name('bindings.driver.end');
        });
    });
