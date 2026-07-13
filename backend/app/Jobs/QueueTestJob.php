<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class QueueTestJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Log::info('QUEUE TEST OK');
    }
}