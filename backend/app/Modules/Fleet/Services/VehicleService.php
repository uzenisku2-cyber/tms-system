<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Modules\Fleet\DTO\VehicleDto;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Repositories\VehicleRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class VehicleService
{
    public function __construct(
        protected VehicleRepository $repository,
    ) {}

    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateFiltered($filters);
    }

    public function create(
        VehicleDto $dto,
    ): Vehicle {
        return $this->repository->createFromDto($dto);
    }

    public function update(
        Vehicle $vehicle,
        VehicleDto $dto,
    ): Vehicle {
        return $this->repository->updateFromDto(
            $vehicle,
            $dto,
        );
    }

    public function delete(
        Vehicle $vehicle,
    ): bool {
        return $this->repository->deleteVehicle($vehicle);
    }
}
