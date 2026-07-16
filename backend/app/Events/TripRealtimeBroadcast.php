<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripRealtimeBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $channelName,
        public array $payload
    ) {
    }


    /**
     * Kam se event vysílá
     */
    public function broadcastOn(): Channel
    {
        return new Channel($this->channelName);
    }


    /**
     * Název eventu pro Echo.listen()
     *
     * Frontend:
     * Echo.channel('trip.6')
     *      .listen('.trip.position.updated', ...)
     */
    public function broadcastAs(): string
    {
        return 'trip.position.updated';
    }


    /**
     * Data poslaná do browseru
     */
    public function broadcastWith(): array
    {
        return [
            'trip_id'  => $this->payload['trip_id'] ?? null,
            'latitude' => $this->payload['latitude'] ?? null,
            'longitude'=> $this->payload['longitude'] ?? null,
            'speed'    => $this->payload['speed'] ?? null,
            'heading'  => $this->payload['heading'] ?? null,
        ];
    }
}