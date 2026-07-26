<?php

declare(strict_types=1);

namespace App\Console\Commands\Generator;

class MakeModelCommand extends AbstractGeneratorCommand
{
    protected $signature = 'tms:model {module} {name}';

    protected $description = 'Create a new module model';

    protected function stub(): string
    {
        return 'model.stub';
    }

    protected function directory(): string
    {
        return 'Models';
    }
}
