<?php

namespace App\Modules\Trips\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Trips\Models\Trip;
use App\Modules\Trips\Models\TripEvent;
use App\Modules\Trips\Models\TripAssignment;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Fleet\Models\Vehicle;
use Illuminate\Http\Request;

class TripAssignmentController extends Controller
{
    public function assign(
        Request $request,
        Trip $trip
    ) {
        if ($trip->user_id !== auth()->id()) {
            abort(403);
        }


        $data = $request->validate([

            'driver_id' => [
                'required',
                'exists:drivers,id',
            ],

            'vehicle_id' => [
                'required',
                'exists:vehicles,id',
            ],

        ]);


        if ($trip->status !== Trip::STATUS_PLANNED) {

            return response()->json([
                'error' => 'Trip is not assignable'
            ], 422);

        }


        $driver = Driver::findOrFail(
            $data['driver_id']
        );


        $vehicle = Vehicle::findOrFail(
            $data['vehicle_id']
        );


        if ($driver->hasActiveTrip()) {

            return response()->json([
                'error' => 'Driver already has active trip'
            ], 422);

        }


        if ($vehicle->hasActiveTrip()) {

            return response()->json([
                'error' => 'Vehicle already has active trip'
            ], 422);

        }


        TripAssignment::create([

            'trip_id' => $trip->id,

            'driver_id' => $driver->id,

            'vehicle_id' => $vehicle->id,

            'assigned_by' => auth()->id(),

            'assigned_at' => now(),

        ]);


        $oldStatus = $trip->status;


        $trip->update([

            'driver_id' => $driver->id,

            'vehicle_id' => $vehicle->id,

            'status' => Trip::STATUS_ASSIGNED,

        ]);


        TripEvent::create([

            'trip_id' => $trip->id,

            'user_id' => auth()->id(),

            'old_status' => $oldStatus,

            'new_status' => Trip::STATUS_ASSIGNED,

        ]);


        return response()->json([

            'status' => 'assigned',

            'data' => $trip->fresh()->load([

                'driver',

                'vehicle',

                'assignments',

                'events',

            ])

        ]);
    }
}