<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehiclePosition;
use App\Events\TripRealtimeBroadcast;
use Illuminate\Http\Request;

class GpsController extends Controller
{

    public function update(Request $request)
    {

        $data = $request->validate([

            'vehicle_id' => [
                'required',
                'integer'
            ],

            'trip_id' => [
                'required',
                'integer'
            ],

            'latitude' => [
                'required',
                'numeric'
            ],

            'longitude' => [
                'required',
                'numeric'
            ],

            'speed' => [
                'nullable',
                'numeric'
            ],

            'heading' => [
                'nullable',
                'numeric'
            ],

        ]);


        /*
        |--------------------------------------------------------------------------
        | ULOŽENÍ GPS HISTORIE
        |--------------------------------------------------------------------------
        */

        VehiclePosition::create([

            'vehicle_id' =>
                $data['vehicle_id'],

            'trip_id' =>
                $data['trip_id'],

            'latitude' =>
                $data['latitude'],

            'longitude' =>
                $data['longitude'],

            'speed' =>
                $data['speed'] ?? 0,

            'heading' =>
                $data['heading'] ?? 0,

        ]);



        /*
        |--------------------------------------------------------------------------
        | REALTIME BROADCAST
        |--------------------------------------------------------------------------
        */

        event(
            new TripRealtimeBroadcast(
                'vehicle.' . $data['vehicle_id'],
                $data
            )
        );



        return response()->json([

            'status' => 'ok',

            'data' => $data

        ]);

    }

}