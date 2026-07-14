<?php

declare(strict_types=1);

namespace App\Modules\Trips\Domain\Listeners;

use App\Core\Events\EventEnvelope;
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


        Cache::put(

            "trip_live_{$payload['trip_id']}",

            [

                'trip_id' => $payload['trip_id'],

                'latitude' => $payload['latitude'],

                'longitude' => $payload['longitude'],

                'speed' => $payload['speed'],

                'heading' => $payload['heading'],

                'updated_at' => $payload['occurred_at'],

            ],

            now()->addMinutes(30)

        );

    }

}