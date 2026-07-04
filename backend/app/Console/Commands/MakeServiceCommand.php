<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Generator\AbstractGeneratorCommand;

class MakeServiceCommand extends AbstractGeneratorCommand
{
    protected $signature = 'make:service {module} {name}';

    protected $description = 'Create a new module service';

    protected function stub(): string
    {
        return 'service.stub';
    }

    protected function directory(): string
    {
        return 'Services';
    }
}
