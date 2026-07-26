<?php

declare(strict_types=1);

namespace App\Core\EventStreaming\Contracts;


interface RealtimeTransport
{

    public function publish(
        string $channel,
        array $payload
    ): void;

}