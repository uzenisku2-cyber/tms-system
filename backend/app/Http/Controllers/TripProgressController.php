<?php

namespace App\Http\Controllers;


use App\Modules\Trips\Models\Trip;



class TripProgressController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Trip progress
    |--------------------------------------------------------------------------
    */


    public function show(
        Trip $trip
    ) {


        $progress = match ($trip->status) {


            Trip::STATUS_PLANNED =>
                0,


            Trip::STATUS_ASSIGNED =>
                10,


            Trip::STATUS_STARTED =>
                20,


            Trip::STATUS_FINISHED =>
                100,


            Trip::STATUS_CANCELLED =>
                0,


            default =>
                0,

        };



        $state = match ($trip->status) {


            Trip::STATUS_FINISHED =>
                'completed',


            Trip::STATUS_CANCELLED =>
                'cancelled',


            Trip::STATUS_STARTED =>
                'in_transit',


            Trip::STATUS_ASSIGNED =>
                'assigned',


            default =>
                'planned',

        };



        return response()->json([


            'trip_id' =>
                $trip->id,


            'status' =>
                $trip->status,


            'progress_percent' =>
                $progress,


            'state' =>
                $state,


        ]);


    }


}