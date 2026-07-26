<?php

declare(strict_types=1);

namespace App\Services;


use App\Models\Alert;
use App\Modules\Trips\Models\Trip;



class AlertService
{


    /**
     * Create alert
     */
    public function create(
        ?Trip $trip,
        string $type,
        string $severity,
        string $message
    ): Alert {

        return Alert::create([

            'trip_id' => $trip?->id,

            'user_id' => auth()->id(),

            'type' => $type,

            'severity' => $severity,

            'message' => $message,

        ]);

    }





    /**
     * ETA delay alert
     */
    public function delay(
        Trip $trip,
        string $message
    ): Alert {

        return $this->create(
            $trip,
            'eta_delay',
            'warning',
            $message
        );

    }





    /**
     * GPS lost alert
     */
    public function gpsWarning(
        Trip $trip,
        string $message
    ): Alert {

        return $this->create(
            $trip,
            'gps_lost',
            'warning',
            $message
        );

    }





    /**
     * Handle trip status change
     */
    public function statusChanged(
        Trip $trip,
        string $oldStatus,
        string $newStatus
    ): void {


        if ($newStatus === Trip::STATUS_STARTED) {

            return;

        }



        if ($newStatus === Trip::STATUS_FINISHED) {


            Alert::where(
                    'trip_id',
                    $trip->id
                )

                ->whereNull('resolved_at')

                ->update([

                    'resolved_at' => now(),

                    'resolved_by' => null,

                ]);

        }

    }





    /**
     * Vehicle idle alert
     */
    public function vehicleIdle(
        Trip $trip,
        string $message
    ): Alert {


        return $this->create(
            $trip,
            'vehicle_idle',
            'warning',
            $message
        );

    }


}