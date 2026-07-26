<?php

declare(strict_types=1);

namespace App\Console\Commands\Generator;

class MakeResourceCommand extends AbstractGeneratorCommand
{
    protected $signature = 'tms:resource {module} {name}';

    protected $description = 'Create a new module resource';

    protected function stub(): string
    {
        return 'resource.stub';
    }

    protected function directory(): string
    {
        return 'Resources';
    }
}
