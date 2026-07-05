<?php

declare(strict_types=1);

namespace App\Modules\Fleet\DTO;

readonly class VehicleDto
{
    public function __construct(
        public string $registrationNumber,
        public string $vin,
        public string $manufacturer,
        public string $model,
        public ?int $year,
        public ?string $fuelType,
        public int $mileage,
        public bool $active,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            registrationNumber: $data['registration_number'],
            vin: $data['vin'],
            manufacturer: $data['manufacturer'],
            model: $data['model'],
            year: $data['year'] ?? null,
            fuelType: $data['fuel_type'] ?? null,
            mileage: $data['mileage'] ?? 0,
            active: $data['active'] ?? true,
        );
    }

    public function toArray(): array
    {
        return [
            'registration_number' => $this->registrationNumber,
            'vin' => $this->vin,
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'year' => $this->year,
            'fuel_type' => $this->fuelType,
            'mileage' => $this->mileage,
            'active' => $this->active,
        ];
    }
}
