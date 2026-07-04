<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Generator\AbstractGeneratorCommand;

class MakeRepositoryCommand extends AbstractGeneratorCommand
{
    protected $signature = 'make:repository {module} {name} {model}';

    protected $description = 'Create a new module repository';

    protected function stub(): string
    {
        return 'repository.stub';
    }

    protected function directory(): string
    {
        return 'Repositories';
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
