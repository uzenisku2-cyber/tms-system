<?php

declare(strict_types=1);

use App\Http\Controllers\Api\GpsController;
use App\Http\Controllers\VehicleController as RealtimeVehicleController;
use App\Http\Controllers\VehiclePositionController;
use App\Http\Middleware\ResolveOrganizationContext;
use App\Modules\DailyReports\Controllers\DailyReportFormConfigurationController;
use App\Modules\Drivers\Controllers\DriverOrganizationAssignmentController;
use App\Modules\Drivers\Controllers\OwnDriverAdminController;
use App\Modules\Organizations\Controllers\CarrierAdminController;
use App\Modules\Organizations\Controllers\OrganizationProfileController;
use App\Modules\Routes\Controllers\RouteCatalogController;
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
        app_path('Modules/DailyReports/Routes/api.php'),
        app_path('Modules/Pricing/Routes/api.php'),
        app_path('Modules/Fleet/Routes/api.php'),
        app_path('Modules/Trips/Routes/api.php'),
        app_path('Modules/Notifications/Routes/api.php'),
    ];

    foreach ($moduleRouteFiles as $moduleRouteFile) {
        if (! is_file($moduleRouteFile)) {
            throw new RuntimeException(
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

// S020-03A CARRIER ADMIN ROUTES
Route::middleware([
    'auth:sanctum',
    'organization',
    'perm:users.manage',
])
    ->prefix('v1/carriers')
    ->group(function (): void {
        Route::get(
            '/',
            [
                CarrierAdminController::class,
                'index',
            ],
        )->name('carriers.index');

        Route::post(
            '/',
            [
                CarrierAdminController::class,
                'store',
            ],
        )->name('carriers.store');
    });

// S020-03B ORGANIZATION PROFILE ROUTES
Route::middleware([
    'auth:sanctum',
    'organization',
    'perm:users.manage',
])
    ->prefix('v1/organization-profile')
    ->group(function (): void {
        Route::get(
            '/',
            [
                OrganizationProfileController::class,
                'show',
            ],
        )->name('organization-profile.show');

        Route::patch(
            '/',
            [
                OrganizationProfileController::class,
                'update',
            ],
        )->name('organization-profile.update');
    });

// S020-03C OWN DRIVER ADMIN ROUTES
Route::middleware([
    'auth:sanctum',
    'organization',
    'perm:users.manage',
])
    ->prefix('v1/own-drivers')
    ->group(function (): void {
        Route::get(
            '/',
            [
                OwnDriverAdminController::class,
                'index',
            ],
        )->name('own-drivers.index');

        Route::post(
            '/',
            [
                OwnDriverAdminController::class,
                'store',
            ],
        )->name('own-drivers.store');
    });

// S020-03D CARRIER UPDATE ROUTE
Route::middleware([
    'auth:sanctum',
    'organization',
    'perm:users.manage',
])
    ->patch(
        'v1/carriers/{carrier}',
        [
            CarrierAdminController::class,
            'update',
        ],
    )
    ->whereNumber('carrier')
    ->name('carriers.update');

// S020-03D OWN DRIVER UPDATE ROUTE
Route::middleware([
    'auth:sanctum',
    'organization',
    'perm:users.manage',
])
    ->patch(
        'v1/own-drivers/{driver}',
        [
            OwnDriverAdminController::class,
            'update',
        ],
    )
    ->whereNumber('driver')
    ->name('own-drivers.update');

// S020-03E OWN DRIVER ACCOUNT LOOKUP ROUTE
Route::middleware([
    'auth:sanctum',
    'organization',
    'perm:users.manage',
])
    ->get(
        'v1/own-drivers/account-lookup',
        [
            OwnDriverAdminController::class,
            'accountLookup',
        ],
    )
    ->name('own-drivers.account-lookup');

// S020-03G DRIVER ASSIGNMENT HISTORY ROUTES
Route::middleware([
    'auth:sanctum',
    'organization',
    'perm:users.manage',
])
    ->prefix('v1/own-drivers/{driver}/assignments')
    ->group(function (): void {
        Route::get(
            '/',
            [
                DriverOrganizationAssignmentController::class,
                'index',
            ],
        )
            ->whereNumber('driver')
            ->name('own-drivers.assignments.index');

        Route::post(
            '/',
            [
                DriverOrganizationAssignmentController::class,
                'store',
            ],
        )
            ->whereNumber('driver')
            ->name('own-drivers.assignments.store');

        Route::patch(
            '/{assignment}/end',
            [
                DriverOrganizationAssignmentController::class,
                'end',
            ],
        )
            ->whereNumber('driver')
            ->whereNumber('assignment')
            ->name('own-drivers.assignments.end');
    });

// S020-04B DAILY REPORT FORM CONFIGURATION ROUTES
Route::middleware([
    'auth:sanctum',
    'organization',
    'perm:daily-reports.view',
])
    ->prefix('v1/daily-report-form')
    ->group(function (): void {
        Route::get(
            '/effective',
            [
                DailyReportFormConfigurationController::class,
                'effective',
            ],
        )->name('daily-report-form.effective');
    });

Route::middleware([
    'auth:sanctum',
    'organization',
    'perm:users.manage',
])
    ->prefix('v1/daily-report-form-configurations')
    ->group(function (): void {
        Route::get(
            '/',
            [
                DailyReportFormConfigurationController::class,
                'index',
            ],
        )->name('daily-report-form-configurations.index');

        Route::post(
            '/',
            [
                DailyReportFormConfigurationController::class,
                'store',
            ],
        )->name('daily-report-form-configurations.store');

        Route::patch(
            '/{configuration}/end',
            [
                DailyReportFormConfigurationController::class,
                'end',
            ],
        )
            ->whereNumber('configuration')
            ->name('daily-report-form-configurations.end');
    });

// S020-04F3A4E2 ROUTE CATALOG API
Route::prefix('v1/settings/catalogs/routes')
    ->middleware([
        'auth:sanctum',
        ResolveOrganizationContext::class,
    ])
    ->group(function (): void {
        Route::get(
            '/',
            [RouteCatalogController::class, 'apiIndex'],
        )->name('api.v1.settings.catalogs.routes.index');

        Route::post(
            '/',
            [RouteCatalogController::class, 'store'],
        )->name('api.v1.settings.catalogs.routes.store');

        Route::patch(
            '/{route}',
            [RouteCatalogController::class, 'update'],
        )->name('api.v1.settings.catalogs.routes.update');

        Route::patch(
            '/{route}/active',
            [RouteCatalogController::class, 'setActive'],
        )->name('api.v1.settings.catalogs.routes.active');
    });
