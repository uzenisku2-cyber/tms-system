<?php

declare(strict_types=1);

namespace App\Support\Generator;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class Generator
{
    public function __construct(
        protected Filesystem $files,
    ) {
    }

    /**
     * Vytvoří adresář, pokud neexistuje.
     */
    public function makeDirectory(string $path): void
    {
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }
    }

    /**
     * Zapíše soubor.
     */
    public function put(string $path, string $contents): void
    {
        $this->makeDirectory(dirname($path));

        $this->files->put($path, $contents);
    }

    /**
     * Zjistí, zda soubor existuje.
     */
    public function exists(string $path): bool
    {
        return $this->files->exists($path);
    }

    /**
     * Načte stub.
     */
    public function getStub(string $stub): string
    {
        $path = base_path("stubs/{$stub}");

        if (! $this->files->exists($path)) {
            throw new RuntimeException(
                "Stub [{$stub}] not found."
            );
        }

        return $this->files->get($path);
    }

    /**
     * Nahradí proměnné ve stubu.
     */
    public function render(
        string $stub,
        array $variables = [],
    ): string {
        $contents = $this->getStub($stub);

        foreach ($variables as $key => $value) {
            $contents = str_replace(
                '{{ '.$key.' }}',
                (string) $value,
                $contents
            );
        }

        return $contents;
    }

    /**
     * Vygeneruje soubor ze stubu.
     */
    public function generate(
        string $stub,
        string $destination,
        array $variables = [],
        bool $force = false,
    ): void {
        if (! $force && $this->exists($destination)) {
            throw new RuntimeException(
                "File already exists: {$destination}"
            );
        }

        $contents = $this->render(
            $stub,
            $variables
        );

        $this->put(
            $destination,
            $contents
        );
    }
}