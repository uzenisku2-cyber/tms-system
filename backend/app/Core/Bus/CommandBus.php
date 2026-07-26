<?php

namespace App\Core\Bus;

use App\Core\Observability\Trace;
use Illuminate\Support\Facades\Bus;

final class CommandBus
{
    public function dispatch(object $command): void
    {
        Trace::log('command.dispatch', [
            'command' => get_class($command),
        ]);

        Bus::dispatch(new ProcessCommandJob($command));
    }
}