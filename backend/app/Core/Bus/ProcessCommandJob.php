<?php

namespace App\Core\Bus;

use App\Core\Observability\Trace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessCommandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public object $command
    ) {}

    public function handle(): void
    {
        Trace::log('job.start', [
            'job' => get_class($this->command),
        ]);

        $handler = $this->resolveHandler($this->command);

        Trace::log('handler.resolve', [
            'handler' => $handler,
        ]);

        app($handler)->handle($this->command);

        Trace::log('job.end', [
            'job' => get_class($this->command),
        ]);
    }

    private function resolveHandler(object $command): string
    {
        $class = get_class($command);

        $class = str_replace(
            '\\Commands\\',
            '\\CommandHandlers\\',
            $class
        );

        $class = preg_replace(
            '/Command$/',
            '',
            $class
        );

        return $class . 'Handler';
    }
}