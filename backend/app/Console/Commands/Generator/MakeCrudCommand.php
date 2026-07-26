<?php

declare(strict_types=1);

namespace App\Console\Commands\Generator;

use Illuminate\Console\Command;

class MakeCrudCommand extends Command
{
    protected $signature = 'tms:crud {module} {name}';

    protected $description = 'Generate complete CRUD structure';

    public function handle(): int
    {
        $module = $this->argument('module');
        $entity = $this->argument('name');

        $this->call('tms:model', [
            'module' => $module,
            'name' => $entity,
        ]);

        $this->call('tms:repository', [
            'module' => $module,
            'name' => "{$entity}Repository",
            'model' => $entity,
        ]);

        $this->call('tms:service', [
            'module' => $module,
            'name' => "{$entity}Service",
        ]);

        $this->call('tms:dto', [
            'module' => $module,
            'name' => "{$entity}Dto",
        ]);

        $this->call('tms:request', [
            'module' => $module,
            'name' => "Store{$entity}Request",
        ]);

        $this->call('tms:request', [
            'module' => $module,
            'name' => "Update{$entity}Request",
        ]);

        $this->call('tms:resource', [
            'module' => $module,
            'name' => "{$entity}Resource",
        ]);

        $this->call('tms:controller', [
            'module' => $module,
            'name' => "{$entity}Controller",
            'service' => "{$entity}Service",
        ]);

        $this->info('CRUD generated successfully.');

        return self::SUCCESS;
    }
}
