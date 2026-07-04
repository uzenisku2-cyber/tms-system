<?php

declare(strict_types=1);

namespace App\Console\Commands\Generator;

use App\Support\Generator\Generator;
use Illuminate\Console\Command;

class MakeModuleCommand extends Command
{
    protected $signature = 'tms:module {name}';

    protected $description = 'Create a new TMS module';

    public function __construct(
        protected Generator $generator,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $module = $this->argument('name');

        $base = app_path("Modules/{$module}");

        $directories = [
            'Actions',
            'Controllers',
            'DTO',
            'Models',
            'Policies',
            'Repositories',
            'Requests',
            'Resources',
            'Routes',
            'Services',
            'Database/Factories',
            'Database/Migrations',
            'Database/Seeders',
        ];

        foreach ($directories as $directory) {
            $this->generator->makeDirectory("{$base}/{$directory}");
        }

        $readme = $this->generator->render(
            'module/README.stub',
            [
                'module' => $module,
            ]
        );

        $this->generator->put(
            "{$base}/README.md",
            $readme
        );

        $this->info("Module {$module} created.");

        return self::SUCCESS;
    }
}
