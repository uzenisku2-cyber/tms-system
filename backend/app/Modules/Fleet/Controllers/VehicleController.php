<?php

namespace App\Modules\Fleet\Controllers;

use App\Core\Bus\CommandBus;
use App\Core\Observability\Trace;
use App\Http\Controllers\Controller;
use App\Modules\Fleet\Application\Commands\CreateVehicleCommand;
use App\Modules\Fleet\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index()
    {
        Trace::log('http.index', [
            'endpoint' => 'vehicles.index',
            'user_id' => auth()->id(),
        ]);

        return response()->json(
            Vehicle::with('user')
                ->where('user_id', auth()->id())
                ->get()
        );
    }


    public function store(Request $request, CommandBus $bus)
    {
        $validated = $request->validate([
            'plate_number' => [
                'required',
                'string',
                'unique:vehicles,registration_number',
            ],

            'manufacturer' => [
                'required',
                'string',
            ],

            'model' => [
                'required',
                'string',
            ],

            'vin' => [
                'nullable',
                'string',
            ],
        ]);


        Trace::log('http.store', [
            'endpoint' => 'vehicles.store',
            'user_id' => auth()->id(),
            'payload' => $validated,
        ]);


        $bus->dispatch(
            new CreateVehicleCommand(
                userId: auth()->id(),
                data: [
                    'plate_number' => $validated['plate_number'],
                    'vin' => $validated['vin'] ?? null,
                    'manufacturer' => $validated['manufacturer'],
                    'model' => $validated['model'],
                ]
            )
        );


        return response()->json([
            'status' => 'queued',
        ], 202);
    }


    public function show(Vehicle $vehicle)
    {
        $this->authorizeVehicle($vehicle);


        Trace::log('http.show', [
            'vehicle_id' => $vehicle->id,
        ]);


        return response()->json(
            $vehicle->load('user')
        );
    }


    public function update(Request $request, Vehicle $vehicle)
    {
        $this->authorizeVehicle($vehicle);


        $validated = $request->validate([
            'manufacturer' => [
                'sometimes',
                'string',
            ],

            'model' => [
                'sometimes',
                'string',
            ],

            'fuel_type' => [
                'nullable',
                'string',
            ],

            'year' => [
                'nullable',
                'integer',
            ],

            'mileage' => [
                'nullable',
                'integer',
            ],

            'active' => [
                'nullable',
                'boolean',
            ],
        ]);


        Trace::log('http.update', [
            'vehicle_id' => $vehicle->id,
            'payload' => $validated,
        ]);


        $vehicle->update($validated);


        return response()->json([
            'status' => 'updated',
            'data' => $vehicle,
        ]);
    }


    public function destroy(Vehicle $vehicle)
    {
        $this->authorizeVehicle($vehicle);


        Trace::log('http.destroy', [
            'vehicle_id' => $vehicle->id,
        ]);


        $vehicle->delete();


        return response()->json([
            'status' => 'deleted',
        ]);
    }


    private function authorizeVehicle(Vehicle $vehicle): void
    {
        if ($vehicle->user_id !== auth()->id()) {
            abort(403, 'Unauthorized vehicle access.');
        }
    }
}