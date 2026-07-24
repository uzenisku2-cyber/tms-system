<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Domain\Services;

use App\Models\Vehicle;
use App\Modules\Fleet\DTO\VehicleDto;

class VehicleDomainService
{
    /**
     * BUSINESS RULE: creation rules live here
     */
    public function create(VehicleDto $dto): Vehicle
    {
        $this->validatePlateFormat($dto->plate);

        return Vehicle::create($dto->toArray());
    }

    /**
     * BUSINESS RULE: plate validation
     */
    private function validatePlateFormat(?string $plate): void
    {
        if (!$plate) {
            throw new \InvalidArgumentException('Plate is required');
        }

        // simple enterprise rule example
        if (!preg_match('/^[0-9A-Z\\-\\s]{3,15}$/', $plate)) {
            throw new \InvalidArgumentException('Invalid plate format');
        }
    }
}