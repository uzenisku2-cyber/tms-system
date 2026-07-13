<?php

namespace App\Http\Controllers;


use App\Modules\Trips\Models\Trip;

use Carbon\Carbon;



class TripEtaController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | ETA information
    |--------------------------------------------------------------------------
    */


    public function show(
        Trip $trip
    ) {


        /*
        |--------------------------------------------------------------------------
        | Finished trip
        |--------------------------------------------------------------------------
        */


        if ($trip->status === Trip::STATUS_FINISHED) {


            return response()->json([

                'trip_id' =>
                    $trip->id,

                'status' =>
                    $trip->status,

                'distance_km' =>
                    0,

                'estimated_minutes' =>
                    0,

                'arrival_time' =>
                    $trip->finished_at,

            ]);


        }





        /*
        |--------------------------------------------------------------------------
        | Cancelled trip
        |--------------------------------------------------------------------------
        */


        if ($trip->status === Trip::STATUS_CANCELLED) {


            return response()->json([

                'trip_id' =>
                    $trip->id,

                'status' =>
                    $trip->status,

                'distance_km' =>
                    null,

                'estimated_minutes' =>
                    null,

                'arrival_time' =>
                    null,

            ]);


        }





        /*
        |--------------------------------------------------------------------------
        | Average speed
        |--------------------------------------------------------------------------
        */


        $averageSpeed = match ($trip->status) {


            Trip::STATUS_STARTED =>

                70,


            Trip::STATUS_ASSIGNED,

            Trip::STATUS_PLANNED =>

                50,


            default =>

                null,


        };





        /*
        |--------------------------------------------------------------------------
        | Calculate remaining distance
        |--------------------------------------------------------------------------
        */


        $distanceKm = $trip->distance_km;



        $location = $trip
            ->locations()
            ->latest()
            ->first();




        if (

            $location

            &&

            $trip->destination_lat

            &&

            $trip->destination_lng

        ) {


            $distanceKm = $this->calculateDistance(

                (float) $location->latitude,

                (float) $location->longitude,

                (float) $trip->destination_lat,

                (float) $trip->destination_lng

            );


        }





        /*
        |--------------------------------------------------------------------------
        | ETA calculation
        |--------------------------------------------------------------------------
        */


        $estimatedMinutes = null;

        $arrivalTime = null;



        if (

            $distanceKm

            &&

            $averageSpeed

        ) {


            $estimatedMinutes = (int) ceil(

                ($distanceKm / $averageSpeed) * 60

            );



            $arrivalTime = Carbon::now()

                ->addMinutes(

                    $estimatedMinutes

                );


        }





        return response()->json([


            'trip_id' =>

                $trip->id,


            'status' =>

                $trip->status,


            'distance_km' =>

                round(

                    $distanceKm,

                    2

                ),



            'source' =>

                $location

                    ? 'current_gps'

                    : 'planned_route',



            'average_speed_kmh' =>

                $averageSpeed,


            'estimated_minutes' =>

                $estimatedMinutes,


            'arrival_time' =>

                $arrivalTime,


        ]);


    }





    /*
    |--------------------------------------------------------------------------
    | Haversine distance
    |--------------------------------------------------------------------------
    */


    protected function calculateDistance(

        float $lat1,

        float $lng1,

        float $lat2,

        float $lng2

    ): float {


        $earthRadius = 6371;



        $dLat = deg2rad(

            $lat2 - $lat1

        );


        $dLng = deg2rad(

            $lng2 - $lng1

        );



        $a =

            sin($dLat / 2) ** 2

            +

            cos(deg2rad($lat1))

            *

            cos(deg2rad($lat2))

            *

            sin($dLng / 2) ** 2;



        $c =

            2 * atan2(

                sqrt($a),

                sqrt(1 - $a)

            );



        return $earthRadius * $c;


    }


}