<?php

declare(strict_types=1);

namespace App\Observers;

use App\Core\EventBus\EventBus;
use App\Models\TripLocation;
use App\Modules\Trips\Domain\Events\TripLocationUpdated;
use Illuminate\Support\Facades\Cache;


class TripLocationObserver
{


    /**
     * Handle the TripLocation "created" event.
     */
    public function created(
        TripLocation $tripLocation
    ): void {

        $this->clearDistanceCache(
            $tripLocation
        );


        EventBus::dispatch(

            TripLocationUpdated::fromLocation(
                $tripLocation
            )

        );

    }



    /**
     * Handle the TripLocation "updated" event.
     */
    public function updated(
        TripLocation $tripLocation
    ): void {

        $this->clearDistanceCache(
            $tripLocation
        );


        EventBus::dispatch(

            TripLocationUpdated::fromLocation(
                $tripLocation
            )

        );

    }



    /**
     * Handle the TripLocation "deleted" event.
     */
    public function deleted(
        TripLocation $tripLocation
    ): void {

        $this->clearDistanceCache(
            $tripLocation
        );

    }




    protected function clearDistanceCache(
        TripLocation $tripLocation
    ): void {

        Cache::forget(

            "trip_distance_{$tripLocation->trip_id}"

        );

    }


}