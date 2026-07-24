<?php

declare(strict_types=1);

namespace App\Modules\Trips\Domain\Listeners;

use App\Core\Events\EventEnvelope;
use App\Core\EventStreaming\RealtimePublisher;
use App\Modules\Trips\Models\Trip;
use Illuminate\Support\Facades\Cache;

class UpdateTripRealtimeProjection
{
    public function handle(
        EventEnvelope $event
    ): void {

        if (

            $event->eventType !==

            'App\Modules\Trips\Domain\Events\TripLocationUpdated'

        ) {

            return;

        }

        $payload = $event->payload;

        $trip = Trip::with([

            'vehicle',

            'driver',

        ])
            ->find(

                $payload['trip_id']

            );

        $vehicle = $trip?->vehicle;
        $driver = $trip?->driver;

        $state = [

            /*
             * TRIP
             */

            'trip_id' => $payload['trip_id'],

            /*
             * VEHICLE
             */

            'vehicle_id' => $vehicle?->getKey(),

            'vehicle_type' => $vehicle?->getAttribute('vehicle_type'),

            'manufacturer' => $vehicle?->getAttribute('manufacturer'),

            'model' => $vehicle?->getAttribute('model'),

            'registration_number' => $vehicle?->getAttribute('registration_number'),

            'color' => $vehicle?->getAttribute('color'),

            /*
             * DRIVER
             */

            'driver_id' => $driver?->getKey(),

            'driver_name' => $driver?->getAttribute('full_name'),

            /*
             * GPS
             */

            'latitude' => $payload['latitude'],

            'longitude' => $payload['longitude'],

            'speed' => $payload['speed'],

            'heading' => $payload['heading'],

            /*
             * STATUS
             */

            'status' => ($payload['speed'] ?? 0) > 0

                    ? 'MOVING'

                    : 'STOPPED',

            'last_seen_at' => $payload['occurred_at'],

        ];

        Cache::put(

            "trip_live_{$payload['trip_id']}",

            $state,

            now()->addMinutes(30)

        );

        RealtimePublisher::publish(

            "trip.{$payload['trip_id']}",

            $state

        );

    }
}
