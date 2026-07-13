<?php

namespace App\Modules\Trips\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Trips\Models\Trip;


class TripTimelineController extends Controller
{

    public function index(Trip $trip)
    {

        if ($trip->user_id !== auth()->id()) {
            abort(403);
        }


        $events = $trip
            ->events()
            ->with('user')
            ->orderBy('created_at')
            ->get();



        return response()->json([

            'trip_id' => $trip->id,

            'timeline' => $events->map(function ($event) {

                return [

                    'id' => $event->id,

                    'old_status' =>
                        $event->old_status,

                    'new_status' =>
                        $event->new_status,

                    'user' => $event->user
                        ? $event->user->name
                        : null,

                    'created_at' =>
                        $event->created_at,

                ];

            }),

        ]);

    }

}