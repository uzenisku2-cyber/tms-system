<?php

declare(strict_types=1);

namespace App\Core\EventStreaming\Transports;

use App\Core\EventStreaming\Contracts\RealtimeTransport;
use App\Events\TripRealtimeBroadcast;


class ReverbRealtimeTransport implements RealtimeTransport
{


    public function publish(
        string $channel,
        array $payload
    ): void {


        broadcast(

            new TripRealtimeBroadcast(
                $channel,
                $payload
            )

        );


    }


}