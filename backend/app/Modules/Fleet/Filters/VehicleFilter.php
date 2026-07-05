<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Filters;

use App\Core\Query\QueryFilter;

class VehicleFilter extends QueryFilter
{
    public function search(string $value): void
    {
        $this->builder->where(function ($q) use ($value) {
            $q->where('registration_number', 'like', "%{$value}%")
                ->orWhere('vin', 'like', "%{$value}%")
                ->orWhere('manufacturer', 'like', "%{$value}%")
                ->orWhere('model', 'like', "%{$value}%");
        });
    }

    public function active(bool $value): void
    {
        $this->builder->where('active', $value);
    }

    public function fuelType(string $value): void
    {
        $this->builder->where('fuel_type', $value);
    }

    public function yearFrom(int $value): void
    {
        $this->builder->where('year', '>=', $value);
    }

    public function yearTo(int $value): void
    {
        $this->builder->where('year', '<=', $value);
    }
}
