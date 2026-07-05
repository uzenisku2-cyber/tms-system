<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Fleet\DTO\VehicleDto;
use App\Modules\Fleet\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public function paginateFiltered(array $filters): LengthAwarePaginator
    {
        $query = Vehicle::query();

        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($query) use ($search): void {
                $query
                    ->where('registration_number', 'like', "%{$search}%")
                    ->orWhere('vin', 'like', "%{$search}%")
                    ->orWhere('manufacturer', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('active', $filters) && $filters['active'] !== null) {
            $query->where('active', (bool) $filters['active']);
        }

        if (! empty($filters['fuel_type'])) {
            $query->where('fuel_type', $filters['fuel_type']);
        }

        if (! empty($filters['year_from'])) {
            $query->where('year', '>=', $filters['year_from']);
        }

        if (! empty($filters['year_to'])) {
            $query->where('year', '<=', $filters['year_to']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }

    public function createFromDto(VehicleDto $dto): Vehicle
    {
        /** @var Vehicle $vehicle */
        $vehicle = $this->create($dto->toArray());

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

    public function deleteVehicle(Vehicle $vehicle): bool
    {
        return $this->delete($vehicle);
    }
}
