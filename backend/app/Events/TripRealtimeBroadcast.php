<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;


class TripRealtimeBroadcast implements ShouldBroadcast
{

    public function __construct(
        public string $channelName,
        public array $payload
    ) {}


    public function broadcastOn(): Channel
    {
        return new Channel(
            $this->channelName
        );
    }


    public function broadcastAs(): string
    {
        return 'trip.position.updated';
    }


    public function broadcastWith(): array
    {
        return $this->payload;
    }

}