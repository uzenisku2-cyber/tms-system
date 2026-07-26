<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Modules\Drivers\Models\Driver;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Trips\Models\Trip;
use App\Modules\Trips\Models\TripAssignment;
use App\Modules\Trips\Models\TripEvent;


class DispatchController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Available resources
    |--------------------------------------------------------------------------
    */

    public function available()
    {

        $drivers = Driver::where('active', true)
            ->get()
            ->filter(function (Driver $driver) {

                return !$driver->hasActiveTrip();

            })
            ->values()
            ->map(function (Driver $driver) {

                return [

                    'id' => $driver->id,

                    'name' =>
                        $driver->first_name
                        . ' '
                        . $driver->last_name,

                    'license_category' =>
                        $driver->license_category,

                ];

            });



        $vehicles = Vehicle::where('active', true)
            ->get()
            ->filter(function (Vehicle $vehicle) {

                return !$vehicle->hasActiveTrip();

            })
            ->values()
            ->map(function (Vehicle $vehicle) {

                return [

                    'id' => $vehicle->id,

                    'registration_number' =>
                        $vehicle->registration_number,

                    'label' =>
                        $vehicle->label,

                ];

            });



        return response()->json([

            'drivers' => $drivers,

            'vehicles' => $vehicles,

        ]);

    }





    /*
    |--------------------------------------------------------------------------
    | Automatic dispatch assignment
    |--------------------------------------------------------------------------
    */

    public function assign(Request $request)
    {

        $data = $request->validate([

            'trip_id' => [
                'required',
                'exists:trips,id'
            ],

        ]);



        return DB::transaction(function () use ($data) {


            $trip = Trip::findOrFail(
                $data['trip_id']
            );



            if ($trip->status !== Trip::STATUS_PLANNED) {

                abort(
                    422,
                    'Trip is not available for assignment'
                );

            }



            $driver = Driver::where('active', true)
                ->get()
                ->first(function (Driver $driver) {

                    return !$driver->hasActiveTrip();

                });



            if (!$driver) {

                abort(
                    422,
                    'No available driver'
                );

            }



            $vehicle = Vehicle::where('active', true)
                ->get()
                ->first(function (Vehicle $vehicle) {

                    return !$vehicle->hasActiveTrip();

                });



            if (!$vehicle) {

                abort(
                    422,
                    'No available vehicle'
                );

            }



            $trip->update([

                'driver_id' =>
                    $driver->id,

                'vehicle_id' =>
                    $vehicle->id,

                'status' =>
                    Trip::STATUS_ASSIGNED,

            ]);



            TripAssignment::create([

                'trip_id' =>
                    $trip->id,

                'driver_id' =>
                    $driver->id,

                'vehicle_id' =>
                    $vehicle->id,

                'assigned_by' =>
                    auth()->id(),

                'assigned_at' =>
                    now(),

            ]);



            TripEvent::create([

                'trip_id' =>
                    $trip->id,

                'user_id' =>
                    auth()->id(),

                'old_status' =>
                    Trip::STATUS_PLANNED,

                'new_status' =>
                    Trip::STATUS_ASSIGNED,

            ]);



            return response()->json([

                'status' =>
                    'assigned',

                'data' =>
                    $trip->fresh(),

            ]);

        });

    }


}