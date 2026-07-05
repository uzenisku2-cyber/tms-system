<?php

declare(strict_types=1);

namespace App\Console\Commands\Generator;

use App\Support\Generator\Generator;
use Illuminate\Console\Command;

abstract class AbstractGeneratorCommand extends Command
{
    public function __construct(
        protected Generator $generator,
    ) {
        parent::__construct();
    }

    abstract protected function stub(): string;

    abstract protected function directory(): string;

    protected function variables(): array
    {
        return [
            'module' => $this->argument('module'),
            'class' => $this->argument('name'),
        ];
    }

    /**
     * Název výsledného souboru.
     */
    protected function filename(): string
    {
        return $this->argument('name').'.php';
    }

    public function handle(): int
    {
        $module = (string) $this->argument('module');
        $class = (string) $this->argument('name');

        $modulePath = app_path("Modules/{$module}");

        if (! is_dir($modulePath)) {
            $this->error("Module '{$module}' does not exist.");

            return self::FAILURE;
        }

        $this->generator->generate(
            $this->stub(),
            "{$modulePath}/{$this->directory()}/{$this->filename()}",
            $this->variables(),
        );

        $this->info("{$class} created successfully.");

        return self::SUCCESS;
    }
}
