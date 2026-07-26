<?php

namespace App\Modules\Fleet\Services;

use App\Modules\Fleet\Application\Commands\CreateVehicleCommand;
use App\Modules\Fleet\Application\CommandHandlers\CreateVehicleHandler;

use App\Modules\Fleet\Application\DTO\VehicleDto;
use App\Modules\Fleet\Domain\Models\Vehicle;

class VehicleService
{
    public function __construct(
        private CreateVehicleHandler $createHandler
    ) {}

    /**
     * CREATE VEHICLE (WRITE CQRS ENTRY POINT)
     */
    public function create(VehicleDto $dto): Vehicle
    {
        $command = new CreateVehicleCommand(
            userId: auth()->id(),
            data: $dto->toArray()
        );

        return $this->createHandler->handle($command);
    }

    /**
     * UPDATE (zatím klasicky – později přes UpdateVehicleCommand)
     */
    public function update(Vehicle $vehicle, VehicleDto $dto): Vehicle
    {
        $vehicle->update($dto->toArray());

        return $vehicle;
    }

    /**
     * DELETE (zatím klasicky – později přes DeleteVehicleCommand)
     */
    public function delete(Vehicle $vehicle): void
    {
        $vehicle->delete();
    }
}