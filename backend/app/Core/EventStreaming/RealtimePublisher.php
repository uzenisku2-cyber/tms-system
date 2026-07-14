<?php

declare(strict_types=1);

namespace App\Core\EventStreaming;

use Illuminate\Support\Facades\Log;


class RealtimePublisher
{

    public static function publish(
        string $channel,
        array $payload
    ): void {

        Log::info(
            'REALTIME_BROADCAST',
            [

                'channel' => $channel,

                'payload' => $payload,

            ]
        );

    }

}