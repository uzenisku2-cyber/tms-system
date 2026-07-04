<?php

declare(strict_types=1);

namespace App\Console\Commands\Generator;

class MakeRequestCommand extends AbstractGeneratorCommand
{
    protected $signature = 'tms:request {module} {name}';

    protected $description = 'Create a new module request';

    protected function stub(): string
    {
        return 'request.stub';
    }

    protected function directory(): string
    {
        return 'Requests';
    }
}
