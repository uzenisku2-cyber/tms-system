<?php

namespace App\Modules\Trips\Application\CommandHandlers;

use App\Modules\Trips\Models\Trip;
use App\Modules\Trips\Domain\Events\TripCreated;

class CreateTripHandler
{
    public function handle($command): Trip
    {
        $trip = Trip::create([

            'user_id' => $command->userId,

            'driver_id' =>
                $command->data['driver_id'],

            'vehicle_id' =>
                $command->data['vehicle_id'],

            'origin' =>
                $command->data['origin'],

            'destination' =>
                $command->data['destination'],

            'status' =>
                $command->data['status'] ?? 'planned',

            'scheduled_at' =>
                $command->data['scheduled_at'] ?? null,

        ]);


        event(new TripCreated(
            tripId: $trip->id,
            payload: $trip->toArray()
        ));


        return $trip;
    }
}