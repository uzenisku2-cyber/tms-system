<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vehicleId = $this->route('vehicle');

        return [
            'registration_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vehicles', 'registration_number')->ignore($vehicleId),
            ],
            'vin' => [
                'required',
                'string',
                'size:17',
                Rule::unique('vehicles', 'vin')->ignore($vehicleId),
            ],
            'manufacturer' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'fuel_type' => ['nullable', 'string', 'max:50'],
            'mileage' => ['required', 'integer', 'min:0'],
            'active' => ['boolean'],
        ];
    }
}
