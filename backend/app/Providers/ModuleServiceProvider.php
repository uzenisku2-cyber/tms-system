<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $modules = app_path('Modules');

        if (! File::exists($modules)) {
            return;
        }

        foreach (File::directories($modules) as $module) {
            $this->loadModule($module);
        }
    }

    protected function loadModule(string $module): void
    {
        $migrationPath = $module.'/Database/Migrations';

        if (File::isDirectory($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }

        $apiRoutes = $module.'/Routes/api.php';

        if (File::exists($apiRoutes)) {
            Route::middleware('api')
                ->prefix('api')
                ->group($apiRoutes);
        }

        $webRoutes = $module.'/Routes/web.php';

        if (File::exists($webRoutes)) {
            Route::middleware('web')
                ->group($webRoutes);
        }
    }
}
