<?php

declare(strict_types=1);

namespace App\Services;


use App\Models\Alert;
use App\Notifications\TripAlertNotification;
use App\Modules\Trips\Models\Trip;

use Carbon\Carbon;



class TripMonitoringService
{


    /*
    |--------------------------------------------------------------------------
    | Check ETA delay
    |--------------------------------------------------------------------------
    */


    public function checkEtaDelay(
        Trip $trip
    ): ?Alert {


        if ($this->isEffectivelyFinished($trip)) {

            $this->resolveFinishedTripAlerts($trip);

            return null;

        }



        if (! $trip->scheduled_at) {

            return null;

        }



        if (

            Carbon::now()->greaterThan(

                Carbon::parse($trip->scheduled_at)

            )

        ) {


            return $this->createAlert(

                $trip,

                'eta_delay',

                'warning',

                "Trip #{$trip->id} is delayed"

            );

        }



        $this->resolveAlert(

            $trip,

            'eta_delay'

        );



        return null;

    }





    /*
    |--------------------------------------------------------------------------
    | GPS lost check
    |--------------------------------------------------------------------------
    */


    public function checkGpsLost(
        Trip $trip,
        int $minutes = 15
    ): ?Alert {


        if ($this->isEffectivelyFinished($trip)) {

            $this->resolveFinishedTripAlerts($trip);

            return null;

        }



        $location = $trip->locations()
            ->latest()
            ->first();



        if (! $location) {


            return $this->createAlert(

                $trip,

                'gps_lost',

                'warning',

                "Trip #{$trip->id} GPS signal lost"

            );

        }




        if (

            Carbon::parse($location->created_at)

                ->addMinutes($minutes)

                ->isPast()

        ) {


            return $this->createAlert(

                $trip,

                'gps_lost',

                'warning',

                "Trip #{$trip->id} GPS signal lost"

            );

        }




        $this->resolveAlert(

            $trip,

            'gps_lost'

        );



        return null;

    }





    /*
    |--------------------------------------------------------------------------
    | Vehicle idle check
    |--------------------------------------------------------------------------
    */


    public function checkVehicleIdle(
        Trip $trip,
        int $minutes = 30
    ): ?Alert {


        if ($this->isEffectivelyFinished($trip)) {

            $this->resolveFinishedTripAlerts($trip);

            return null;

        }



        $location = $trip->locations()
            ->latest()
            ->first();



        if (! $location) {

            return null;

        }



        if (

            $location->speed == 0

            &&

            Carbon::parse($location->created_at)

                ->addMinutes($minutes)

                ->isPast()

        ) {


            return $this->createAlert(

                $trip,

                'vehicle_idle',

                'info',

                "Trip #{$trip->id} vehicle is idle"

            );

        }




        $this->resolveAlert(

            $trip,

            'vehicle_idle'

        );



        return null;

    }





    /*
    |--------------------------------------------------------------------------
    | Finished check
    |--------------------------------------------------------------------------
    */


    protected function isEffectivelyFinished(
        Trip $trip
    ): bool {


        return $trip->status === Trip::STATUS_FINISHED;


    }





    /*
    |--------------------------------------------------------------------------
    | Resolve finished trip alerts
    |--------------------------------------------------------------------------
    */


    protected function resolveFinishedTripAlerts(
        Trip $trip
    ): void {


        Alert::where(

                'trip_id',

                $trip->id

            )

            ->whereIn(

                'type',

                [

                    'gps_lost',

                    'eta_delay',

                    'vehicle_idle',

                ]

            )

            ->whereNull('resolved_at')

            ->update([

                'resolved_at' => now(),

                'resolved_by' => null,

            ]);


    }





    /*
    |--------------------------------------------------------------------------
    | Resolve alert
    |--------------------------------------------------------------------------
    */


    protected function resolveAlert(
        Trip $trip,
        string $type
    ): void {


        Alert::where(

                'trip_id',

                $trip->id

            )

            ->where(

                'type',

                $type

            )

            ->whereNull('resolved_at')

            ->update([

                'resolved_at' => now(),

                'resolved_by' => null,

            ]);


    }





    /*
    |--------------------------------------------------------------------------
    | Create alert
    |--------------------------------------------------------------------------
    */


    protected function createAlert(
        Trip $trip,
        string $type,
        string $severity,
        string $message
    ): Alert {


        $existing = Alert::where(

                'trip_id',

                $trip->id

            )

            ->where(

                'type',

                $type

            )

            ->whereNull('resolved_at')

            ->first();



        if ($existing) {

            return $existing;

        }




        $alert = Alert::create([


            'trip_id' =>

                $trip->id,


            'user_id' =>

                $trip->user_id,


            'type' =>

                $type,


            'severity' =>

                $severity,


            'message' =>

                $message,


        ]);




        if ($trip->user) {


            $trip->user->notify(

                new TripAlertNotification($alert)

            );


        }



        return $alert;


    }


}