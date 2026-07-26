<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Trips\Models\Trip;
use Illuminate\Support\Facades\Cache;


class TripRealtimeController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Realtime trip state from projection cache
    |--------------------------------------------------------------------------
    */


    public function show(
        Trip $trip
    ) {


        $state = Cache::get(

            "trip_live_{$trip->id}"

        );



        return response()->json([


            'trip_id' =>

                $trip->id,



            'realtime' =>

                $state,



        ]);

    }


}