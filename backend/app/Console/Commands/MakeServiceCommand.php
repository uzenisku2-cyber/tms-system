<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Generator\Generator;
use Illuminate\Console\Command;

class MakeServiceCommand extends Command
{
    protected $signature = 'make:service {module} {name}';

    protected $description = 'Create a new module service';

    public function __construct(
        protected Generator $generator,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $module = $this->argument('module');
        $class = $this->argument('name');

        $this->generator->generate(
            'service.stub',
            app_path(
                "Modules/{$module}/Services/{$class}.php"
            ),
            [
                'module' => $module,
                'class' => $class,
            ]
        );

        $this->info("Service {$class} created.");

        return self::SUCCESS;
    }
}