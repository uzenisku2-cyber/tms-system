<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerModules();
    }

    private function registerModules(): void
    {
        $modulesPath = app_path('Modules');

        if (!File::exists($modulesPath)) {
            return;
        }

        foreach (File::directories($modulesPath) as $module) {
            $this->registerModule($module);
        }
    }

    private function registerModule(string $module): void
    {
        $this->loadMigrations($module);

        // ❌ IMPORTANT: routes are NOT loaded here anymore
        // routing is handled ONLY in routes/api.php (gateway)
    }

    private function loadMigrations(string $module): void
    {
        $migrationPath = $module . '/Database/Migrations';

        if (File::isDirectory($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }
    }
}