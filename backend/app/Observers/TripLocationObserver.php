<?php

namespace App\Observers;

use App\Models\TripLocation;
use Illuminate\Support\Facades\Cache;


class TripLocationObserver
{


    /**
     * Handle the TripLocation "created" event.
     */
    public function created(
        TripLocation $tripLocation
    ): void
    {

        $this->clearDistanceCache(
            $tripLocation
        );

    }



    /**
     * Handle the TripLocation "updated" event.
     */
    public function updated(
        TripLocation $tripLocation
    ): void
    {

        $this->clearDistanceCache(
            $tripLocation
        );

    }



    /**
     * Handle the TripLocation "deleted" event.
     */
    public function deleted(
        TripLocation $tripLocation
    ): void
    {

        $this->clearDistanceCache(
            $tripLocation
        );

    }




    protected function clearDistanceCache(
        TripLocation $tripLocation
    ): void
    {

        Cache::forget(
            "trip_distance_{$tripLocation->trip_id}"
        );

    }


}