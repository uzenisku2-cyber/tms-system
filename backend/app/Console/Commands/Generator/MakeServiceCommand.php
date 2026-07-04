<?php

declare(strict_types=1);

namespace App\Console\Commands\Generator;

class MakeServiceCommand extends AbstractGeneratorCommand
{
    protected $signature = 'tms:service {module} {name}';

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
