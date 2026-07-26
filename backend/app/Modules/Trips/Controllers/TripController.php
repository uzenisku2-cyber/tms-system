<?php

declare(strict_types=1);


namespace App\Modules\Trips\Controllers;


use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Modules\Trips\Models\Trip;
use App\Modules\Trips\Models\TripEvent;

use Illuminate\Support\Facades\Auth;



class TripController extends Controller
{


    public function index()
    {

        return response()->json(

            Trip::with([

                'driver',
                'vehicle',
                'events',
                'assignments'

            ])

            ->latest()

            ->get()

        );

    }





    public function show(
        Trip $trip
    )
    {

        $this->authorizeTrip($trip);


        return response()->json(

            $trip->load([

                'user',
                'driver',
                'vehicle',
                'events',
                'assignments.driver',
                'assignments.vehicle',
                'assignments.assignedBy',
                'locations',

            ])

        );

    }





    public function store(
        Request $request
    )
    {


        $validated = $request->validate([


            'driver_id' => [

                'nullable',
                'exists:drivers,id'

            ],


            'vehicle_id' => [

                'nullable',
                'exists:vehicles,id'

            ],


            'origin' => [

                'required',
                'string'

            ],


            'destination' => [

                'required',
                'string'

            ],


            'origin_lat' => [

                'nullable',
                'numeric',
                'between:-90,90'

            ],


            'origin_lng' => [

                'nullable',
                'numeric',
                'between:-180,180'

            ],


            'destination_lat' => [

                'nullable',
                'numeric',
                'between:-90,90'

            ],


            'destination_lng' => [

                'nullable',
                'numeric',
                'between:-180,180'

            ],


            'scheduled_at' => [

                'nullable',
                'date'

            ],


        ]);




        $distance = null;



        if (

            isset($validated['origin_lat'])

            &&

            isset($validated['origin_lng'])

            &&

            isset($validated['destination_lat'])

            &&

            isset($validated['destination_lng'])

        ) {


            $distance = $this->calculateDistance(

                $validated['origin_lat'],

                $validated['origin_lng'],

                $validated['destination_lat'],

                $validated['destination_lng']

            );


        }






        $trip = Trip::create([


            'user_id' =>

                auth()->id(),



            'driver_id' =>

                $validated['driver_id'] ?? null,



            'vehicle_id' =>

                $validated['vehicle_id'] ?? null,



            'origin' =>

                $validated['origin'],



            'destination' =>

                $validated['destination'],



            'origin_lat' =>

                $validated['origin_lat'] ?? null,



            'origin_lng' =>

                $validated['origin_lng'] ?? null,



            'destination_lat' =>

                $validated['destination_lat'] ?? null,



            'destination_lng' =>

                $validated['destination_lng'] ?? null,



            'distance_km' =>

                $distance,



            'scheduled_at' =>

                $validated['scheduled_at'] ?? null,



            'status' =>

                Trip::STATUS_PLANNED,


        ]);





        return response()->json([


            'status' =>

                'created',



            'data' =>

                $trip


        ], 201);


    }





    public function update(
        Request $request,
        Trip $trip
    )
    {


        $this->authorizeTrip($trip);



        $validated = $request->validate([


            'status' => [

                'nullable',
                'string'

            ],


            'started_at' => [

                'nullable',
                'date'

            ],


            'finished_at' => [

                'nullable',
                'date'

            ],


            'cancel_reason' => [

                'nullable',
                'string'

            ],


        ]);




        $oldStatus = $trip->status;




        if (

            isset($validated['status'])

            &&

            $validated['status'] !== $oldStatus

        ) {


            if (

                ! $trip->canChangeStatus(

                    $validated['status']

                )

            ) {


                return response()->json([


                    'message' =>

                        'Invalid trip status transition',


                    'current_status' =>

                        $oldStatus,


                    'requested_status' =>

                        $validated['status'],


                ], 422);


            }




            $trip->status =
                $validated['status'];





            switch ($validated['status']) {


                case Trip::STATUS_STARTED:

                    $trip->started_at = now();

                    break;



                case Trip::STATUS_FINISHED:

                    $trip->finished_at = now();

                    break;



                case Trip::STATUS_CANCELLED:

                    $trip->cancelled_at = now();

                    $trip->cancelled_by = auth()->id();

                    break;


            }





            TripEvent::create([


                'trip_id' =>

                    $trip->id,


                'user_id' =>

                    auth()->id(),


                'old_status' =>

                    $oldStatus,


                'new_status' =>

                    $validated['status'],


            ]);


        }




        if (isset($validated['started_at'])) {

            $trip->started_at =
                $validated['started_at'];

        }




        if (isset($validated['finished_at'])) {

            $trip->finished_at =
                $validated['finished_at'];

        }




        if (isset($validated['cancel_reason'])) {

            $trip->cancel_reason =
                $validated['cancel_reason'];

        }



        $trip->save();




        return response()->json([


            'status' =>

                'updated',



            'data' =>

                $trip


        ]);


    }





    public function destroy(
        Trip $trip
    )
    {


        $this->authorizeTrip($trip);



        $trip->delete();



        return response()->json([


            'status' =>

                'deleted'


        ]);

    }





    protected function calculateDistance(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float
    {


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



        return round(

            $earthRadius * $c,

            2

        );


    }





    protected function authorizeTrip(
        Trip $trip
    ): void
    {


        if (

            auth()->id()
            !==
            $trip->user_id

        ) {


            abort(

                403,

                'Unauthorized trip access'

            );


        }


    }


}