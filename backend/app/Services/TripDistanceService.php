<?php

namespace App\Services;

use App\Modules\Trips\Models\Trip;
use Illuminate\Support\Facades\Cache;


class TripDistanceService
{


    /**
     * Resolve trip distance.
     *
     * Priority:
     * 1. Stored distance
     * 2. Cached calculation
     * 3. GPS locations
     * 4. Origin/destination coordinates
     * 5. City fallback
     */
    public function calculate(
        Trip $trip
    ): float
    {

        if (
            $trip->distance_km !== null
        ) {

            return (float) $trip->distance_km;

        }



        return Cache::remember(

            'trip_distance_' . $trip->id,

            now()->addHours(24),

            function () use ($trip) {

                return $this->calculateFromData(
                    $trip
                );

            }

        );


    }





    /**
     * Calculate distance from trip data.
     */
    protected function calculateFromData(
        Trip $trip
    ): float
    {


        /*
        |--------------------------------------------------------------------------
        | Use already loaded locations
        |--------------------------------------------------------------------------
        */

        $locations =

            $trip->relationLoaded('locations')

                ?

                $trip->locations

                :

                $trip->locations()

                    ->orderBy(
                        'created_at'
                    )

                    ->get();





        /*
        |--------------------------------------------------------------------------
        | 1. GPS locations
        |--------------------------------------------------------------------------
        */

        if (
            $locations->count() > 1
        ) {


            $distance = 0;



            for (
                $i = 1;
                $i < $locations->count();
                $i++
            ) {


                $distance +=

                    $this->haversine(

                        $locations[$i - 1]->latitude,

                        $locations[$i - 1]->longitude,

                        $locations[$i]->latitude,

                        $locations[$i]->longitude

                    );


            }



            if (
                $distance > 0
            ) {

                return $distance;

            }


        }





        /*
        |--------------------------------------------------------------------------
        | 2. Origin / destination coordinates
        |--------------------------------------------------------------------------
        */

        if (

            $trip->origin_lat !== null

            &&

            $trip->origin_lng !== null

            &&

            $trip->destination_lat !== null

            &&

            $trip->destination_lng !== null

        ) {


            return $this->haversine(

                $trip->origin_lat,

                $trip->origin_lng,

                $trip->destination_lat,

                $trip->destination_lng

            );


        }





        /*
        |--------------------------------------------------------------------------
        | 3. City fallback
        |--------------------------------------------------------------------------
        */

        return $this->calculateCityDistance(
            $trip
        );


    }





    /**
     * City fallback distances.
     */
    protected function calculateCityDistance(
        Trip $trip
    ): float
    {


        $cities = [


            'Praha|Brno' => 185,
            'Brno|Praha' => 185,


            'Praha|Berlin' => 350,
            'Berlin|Praha' => 350,


            'Ostrava|Plzen' => 430,
            'Plzen|Ostrava' => 430,


            'Liberec|Olomouc' => 260,
            'Olomouc|Liberec' => 260,


            'Brno|Vienna' => 140,
            'Vienna|Brno' => 140,


        ];



        $key =

            trim($trip->origin)

            .

            '|'

            .

            trim($trip->destination);



        return isset(
            $cities[$key]
        )

            ?

            (float) $cities[$key]

            :

            0;


    }





    /**
     * Haversine formula.
     */
    protected function haversine(
        $lat1,
        $lon1,
        $lat2,
        $lon2
    ): float
    {


        $earthRadius = 6371;



        $dLat = deg2rad(
            $lat2 - $lat1
        );


        $dLon = deg2rad(
            $lon2 - $lon1
        );



        $a =

            sin($dLat / 2) ** 2

            +

            cos(
                deg2rad($lat1)
            )

            *

            cos(
                deg2rad($lat2)
            )

            *

            sin($dLon / 2) ** 2;



        return

            $earthRadius

            *

            2

            *

            atan2(

                sqrt($a),

                sqrt(1 - $a)

            );


    }


}