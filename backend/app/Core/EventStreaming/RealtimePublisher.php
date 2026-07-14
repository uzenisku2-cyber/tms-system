<?php

declare(strict_types=1);

namespace App\Core\EventStreaming;

use App\Core\EventStreaming\Contracts\RealtimeTransport;


class RealtimePublisher
{


    public static function publish(
        string $channel,
        array $payload
    ): void {


        app(

            RealtimeTransport::class

        )->publish(

            $channel,

            $payload

        );


    }


}