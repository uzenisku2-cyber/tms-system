<?php

declare(strict_types=1);

namespace App\Modules\Trips\Domain\Events;


use App\Models\TripLocation;


class TripLocationUpdated
{

    public function __construct(
        public readonly int $tripId,
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

        return new self(

            tripId: $location->trip_id,

            latitude: (float) $location->latitude,

            longitude: (float) $location->longitude,

            speed: $location->speed,

            heading: $location->heading,

            occurredAt: now()->toISOString(),

        );

    }



    public function toArray(): array
    {
        return [

            'trip_id' => $this->tripId,

            'latitude' => $this->latitude,

            'longitude' => $this->longitude,

            'speed' => $this->speed,

            'heading' => $this->heading,

            'occurred_at' => $this->occurredAt,

        ];
    }

}