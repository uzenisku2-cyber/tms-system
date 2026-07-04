<?php

declare(strict_types=1);

namespace App\Console\Commands\Generator;

class MakeDtoCommand extends AbstractGeneratorCommand
{
    protected $signature = 'tms:dto {module} {name}';

    protected $description = 'Create a new module DTO';

    protected function stub(): string
    {
        return 'dto.stub';
    }

    protected function directory(): string
    {
        return 'DTO';
    }
}
