<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Resources;

use App\Modules\Fleet\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Vehicle $vehicle */
        $vehicle = $this->resource;

        return [
            'id' => $vehicle->id,
            'registration_number' => $vehicle->registration_number,
            'vin' => $vehicle->vin,
            'manufacturer' => $vehicle->manufacturer,
            'model' => $vehicle->model,
            'year' => $vehicle->year,
            'fuel_type' => $vehicle->fuel_type,
            'mileage' => $vehicle->mileage,
            'active' => $vehicle->active,
            'created_at' => $vehicle->created_at,
            'updated_at' => $vehicle->updated_at,
        ];
    }
}
