<?php

namespace App\Modules\Drivers\Controllers;

use App\Core\Bus\CommandBus;
use App\Core\Observability\Trace;
use App\Http\Controllers\Controller;
use App\Modules\Drivers\Application\Commands\CreateDriverCommand;
use App\Modules\Drivers\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index()
    {
        return response()->json(
            Driver::with('user')
                ->where('user_id', auth()->id())
                ->get()
        );
    }


    public function store(
        Request $request,
        CommandBus $bus
    ) {

        $validated = $request->validate([

            'first_name' =>
                'required|string',

            'last_name' =>
                'required|string',

            'license_number' =>
                'required|string|unique:drivers,license_number',

            'phone' =>
                'nullable|string',

            'email' =>
                'nullable|email',

            'license_category' =>
                'nullable|string',

        ]);


        Trace::log('driver.store', [
            'user_id' => auth()->id(),
            'payload' => $validated,
        ]);


        $bus->dispatch(
            new CreateDriverCommand(
                userId: auth()->id(),
                data: $validated
            )
        );


        return response()->json([
            'status' => 'queued',
        ], 202);
    }


    public function show(Driver $driver)
    {
        $this->authorizeDriver($driver);

        return response()->json(
            $driver->load('user')
        );
    }


    public function update(
        Request $request,
        Driver $driver
    ) {

        $this->authorizeDriver($driver);


        $driver->update(
            $request->validate([
                'phone' => 'nullable|string',
                'email' => 'nullable|email',
                'license_category' => 'nullable|string',
                'active' => 'nullable|boolean',
            ])
        );


        return response()->json([
            'status' => 'updated',
            'data' => $driver,
        ]);
    }


    public function destroy(Driver $driver)
    {
        $this->authorizeDriver($driver);

        $driver->delete();

        return response()->json([
            'status' => 'deleted',
        ]);
    }


    private function authorizeDriver(Driver $driver): void
    {
        if ($driver->user_id !== auth()->id()) {
            abort(403);
        }
    }
}