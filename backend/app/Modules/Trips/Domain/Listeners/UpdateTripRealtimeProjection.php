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

            'driver'

        ])

        ->find(

            $payload['trip_id']

        );






        $state = [



            /*
             * TRIP
             */

            'trip_id' =>

                $payload['trip_id'],







            /*
             * VEHICLE
             */

            'vehicle_id' =>

                $trip?->vehicle?->id,


            'vehicle_type' =>

                $trip?->vehicle?->vehicle_type,


            'manufacturer' =>

                $trip?->vehicle?->manufacturer,


            'model' =>

                $trip?->vehicle?->model,


            'registration_number' =>

                $trip?->vehicle?->registration_number,


            'color' =>

                $trip?->vehicle?->color,







            /*
             * DRIVER
             */

            'driver_id' =>

                $trip?->driver?->id,


            'driver_name' =>

                $trip?->driver?->full_name,







            /*
             * GPS
             */

            'latitude' =>

                $payload['latitude'],


            'longitude' =>

                $payload['longitude'],


            'speed' =>

                $payload['speed'],


            'heading' =>

                $payload['heading'],







            /*
             * STATUS
             */

            'status' =>

                ($payload['speed'] ?? 0) > 0

                    ? 'MOVING'

                    : 'STOPPED',






            'last_seen_at' =>

                $payload['occurred_at'],



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