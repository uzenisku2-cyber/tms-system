<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'plate' => $this->plate,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}