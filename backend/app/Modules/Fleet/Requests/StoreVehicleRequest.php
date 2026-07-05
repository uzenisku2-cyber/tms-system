<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'registration_number' => ['required', 'string', 'max:20', 'unique:vehicles,registration_number'],
            'vin' => ['required', 'string', 'size:17', 'unique:vehicles,vin'],
            'manufacturer' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'fuel_type' => ['nullable', 'string', 'max:50'],
            'mileage' => ['required', 'integer', 'min:0'],
            'active' => ['boolean'],
        ];
    }
}
