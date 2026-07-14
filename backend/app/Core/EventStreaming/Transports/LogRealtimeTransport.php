<?php

declare(strict_types=1);

namespace App\Core\EventStreaming\Transports;

use App\Core\EventStreaming\Contracts\RealtimeTransport;
use Illuminate\Support\Facades\Log;


class LogRealtimeTransport implements RealtimeTransport
{


    public function publish(
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