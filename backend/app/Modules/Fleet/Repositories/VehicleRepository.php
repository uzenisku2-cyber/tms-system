<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Fleet\DTO\VehicleDto;
use App\Modules\Fleet\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;

class VehicleRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Vehicle);
    }

    public function all(): Collection
    {
        return Vehicle::query()
            ->orderBy('registration_number')
            ->get();
    }

    public function createFromDto(
        VehicleDto $dto,
    ): Vehicle {
        /** @var Vehicle $vehicle */
        $vehicle = $this->create(
            $dto->toArray()
        );

        return $vehicle;
    }

    public function updateFromDto(
        Vehicle $vehicle,
        VehicleDto $dto,
    ): Vehicle {
        /** @var Vehicle $vehicle */
        $vehicle = $this->update(
            $vehicle,
            $dto->toArray()
        );

        return $vehicle;
    }

    public function deleteVehicle(
        Vehicle $vehicle,
    ): bool {
        return $this->delete($vehicle);
    }
}
