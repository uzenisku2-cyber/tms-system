<?php

declare(strict_types=1);

namespace App\Modules\Trips\Domain\Events;


use App\Models\TripLocation;


class TripLocationUpdated
{

    public function __construct(

        public readonly int $tripId,

        public readonly ?int $vehicleId,

        public readonly float $latitude,

        public readonly float $longitude,

        public readonly ?int $speed,

        public readonly ?int $heading,

        public readonly string $occurredAt,

    ) {
    }





    public static function fromLocation(
        TripLocation $location
    ): self {


        $location->load(
            'trip'
        );



        return new self(

            tripId:
                $location->trip_id,



            vehicleId:
                $location->trip->vehicle_id,



            latitude:
                (float) $location->latitude,



            longitude:
                (float) $location->longitude,



            speed:
                $location->speed,



            heading:
                $location->heading,



            occurredAt:
                now()->toISOString(),

        );

    }





    public function toArray(): array
    {

        return [

            'trip_id' =>
                $this->tripId,



            'vehicle_id' =>
                $this->vehicleId,



            'latitude' =>
                $this->latitude,



            'longitude' =>
                $this->longitude,



            'speed' =>
                $this->speed,



            'heading' =>
                $this->heading,



            'occurred_at' =>
                $this->occurredAt,

        ];

    }

}