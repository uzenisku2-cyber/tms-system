<?php

declare(strict_types=1);

namespace App\Console\Commands\Generator;

class MakeFactoryCommand extends AbstractGeneratorCommand
{
    protected $signature = 'tms:factory {module} {name} {model}';

    protected $description = 'Create a new module factory';

    protected function stub(): string
    {
        return 'factory.stub';
    }

    protected function directory(): string
    {
        return 'Database/Factories';
    }

    protected function variables(): array
    {
        return [
            'module' => $this->argument('module'),
            'class' => $this->argument('name'),
            'model' => $this->argument('model'),
        ];
    }
}
