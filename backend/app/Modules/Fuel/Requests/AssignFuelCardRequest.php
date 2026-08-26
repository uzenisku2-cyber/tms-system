<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssignFuelCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'responsible_organization_id' => ['required', 'integer', 'min:1'],
            'driver_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_id' => ['nullable', 'integer', 'min:1', 'exists:vehicles,id'],
            'assignment_type' => ['required', Rule::in(['organization', 'driver', 'vehicle', 'driver_vehicle', 'temporary', 'shared_pool'])],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'usage_restrictions' => ['nullable', 'string', 'max:2000'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
