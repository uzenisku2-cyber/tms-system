<?php

declare(strict_types=1);

namespace App\Console\Commands\Generator;

class MakeSeederCommand extends AbstractGeneratorCommand
{
    protected $signature = 'tms:seeder {module} {name}';

    protected $description = 'Create a new module seeder';

    protected function stub(): string
    {
        return 'seeder.stub';
    }

    protected function directory(): string
    {
        return 'Database/Seeders';
    }
}
