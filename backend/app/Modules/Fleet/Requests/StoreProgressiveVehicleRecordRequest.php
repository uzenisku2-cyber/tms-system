<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreProgressiveVehicleRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('vehicle.manage') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'registration_number' => ['nullable', 'string', 'max:32', 'required_without:vin', Rule::unique('vehicles', 'registration_number')],
            'vin' => ['nullable', 'string', 'max:64', 'required_without:registration_number', Rule::unique('vehicles', 'vin')],
            'manufacturer' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'year' => ['nullable', 'integer', 'min:1886', 'max:'.(now()->year + 1)],
            'fuel_type' => ['nullable', 'string', 'max:64'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
