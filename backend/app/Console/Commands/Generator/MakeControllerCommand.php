<?php

declare(strict_types=1);

namespace App\Console\Commands\Generator;

class MakeControllerCommand extends AbstractGeneratorCommand
{
    protected $signature = 'tms:controller {module} {name} {service}';

    protected $description = 'Create a new module controller';

    protected function stub(): string
    {
        return 'controller.stub';
    }

    protected function directory(): string
    {
        return 'Controllers';
    }

    protected function variables(): array
    {
        return [
            'module' => $this->argument('module'),
            'class' => $this->argument('name'),
            'service' => $this->argument('service'),
        ];
    }
}
