<?php

namespace App\Modules\Fleet\Application\CommandHandlers;

use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Domain\Events\VehicleCreated;

class CreateVehicleHandler
{
    public function handle($command): Vehicle
    {
        $vehicle = Vehicle::create([
            'user_id' => $command->userId,

            'registration_number' =>
                $command->data['plate_number'] ?? 'UNKNOWN',

            'vin' =>
                $command->data['vin']
                ?? 'AUTO-' . strtoupper(uniqid()),

            'manufacturer' =>
                $command->data['manufacturer']
                ?? 'Unknown',

            'model' =>
                $command->data['model']
                ?? 'Unknown',

            'active' => true,

            'mileage' => 0,
        ]);

        event(new VehicleCreated(
            vehicleId: $vehicle->id,
            payload: $vehicle->toArray()
        ));

        return $vehicle;
    }
}