<?php

declare(strict_types=1);

namespace App\Console\Commands\Generator;

use Illuminate\Support\Str;

class MakeMigrationCommand extends AbstractGeneratorCommand
{
    protected $signature = 'tms:migration {module} {name}';

    protected $description = 'Create a new module migration';

    protected function stub(): string
    {
        return 'migration.stub';
    }

    protected function directory(): string
    {
        return 'Database/Migrations';
    }

    protected function filename(): string
    {
        return now()->format('Y_m_d_His')
            .'_create_'
            .Str::snake(Str::pluralStudly($this->argument('name')))
            .'_table.php';
    }

    protected function variables(): array
    {
        return [
            'module' => $this->argument('module'),
            'class' => $this->argument('name'),
            'table' => Str::snake(Str::pluralStudly($this->argument('name'))),
        ];
    }
}
