<?php

declare(strict_types=1);

namespace App\Core\EventStreaming;

use App\Core\EventStreaming\Contracts\RealtimeTransport;
use App\Core\EventStreaming\Transports\LogRealtimeTransport;


class RealtimePublisher
{


    public static function publish(
        string $channel,
        array $payload
    ): void {


        $transport = new LogRealtimeTransport();


        $transport->publish(

            $channel,

            $payload

        );


    }


}