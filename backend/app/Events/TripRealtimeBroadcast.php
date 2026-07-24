<?php

declare(strict_types=1);

namespace App\Events;


use Illuminate\Broadcasting\PrivateChannel;
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





    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel(
            $this->channelName
        );
    }





    public function broadcastAs(): string
    {
        return 'trip.position.updated';
    }





    public function broadcastWith(): array
    {


        return [


            /*
             * Trip
             */

            'trip_id' =>
                $this->payload['trip_id']
                ?? null,



            /*
             * Vehicle
             */

            'vehicle_id' =>
                $this->payload['vehicle_id']
                ?? null,


            'vehicle_type' =>
                $this->payload['vehicle_type']
                ?? null,


            'manufacturer' =>
                $this->payload['manufacturer']
                ?? null,


            'model' =>
                $this->payload['model']
                ?? null,


            'registration_number' =>
                $this->payload['registration_number']
                ?? null,


            'color' =>
                $this->payload['color']
                ?? null,



            /*
             * Driver
             */

            'driver_id' =>
                $this->payload['driver_id']
                ?? null,


            'driver_name' =>
                $this->payload['driver_name']
                ?? null,



            /*
             * GPS
             */

            'latitude' =>
                $this->payload['latitude']
                ?? null,


            'longitude' =>
                $this->payload['longitude']
                ?? null,


            'speed' =>
                $this->payload['speed']
                ?? 0,


            'heading' =>
                $this->payload['heading']
                ?? 0,



            /*
             * Status
             */

            'status' =>
                $this->payload['status']
                ?? 'STOPPED',



            'last_seen_at' =>
                $this->payload['last_seen_at']
                ?? now()->toDateTimeString(),


        ];

    }


}