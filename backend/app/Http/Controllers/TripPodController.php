<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Modules\Trips\Models\Trip;
use App\Modules\Trips\Models\TripPod;


class TripPodController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Create Proof of Delivery
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        Trip $trip
    ) {


        if ($trip->status !== Trip::STATUS_FINISHED) {

            abort(
                422,
                'POD can only be created for finished trip'
            );

        }



        if ($trip->pod) {

            abort(
                422,
                'POD already exists'
            );

        }



        $validated = $request->validate([

            'recipient' => [
                'required',
                'string',
                'max:255'
            ],


            'note' => [
                'nullable',
                'string'
            ],

        ]);



        $pod = TripPod::create([

            'trip_id' =>
                $trip->id,

            'recipient' =>
                $validated['recipient'],

            'note' =>
                $validated['note'] ?? null,

            'delivered_at' =>
                now(),

            'delivered_by' =>
                auth()->id(),

        ]);



        return response()->json([

            'status' =>
                'created',

            'data' =>
                $pod,

        ], 201);

    }


}