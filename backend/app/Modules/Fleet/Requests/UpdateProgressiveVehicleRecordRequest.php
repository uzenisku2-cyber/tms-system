<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProgressiveVehicleRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('vehicle.manage') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'expected_revision' => ['required', 'integer', 'min:1'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.registration_number' => ['sometimes', 'nullable', 'string', 'max:32'],
            'fields.vin' => ['sometimes', 'nullable', 'string', 'max:64'],
            'fields.manufacturer' => ['sometimes', 'nullable', 'string', 'max:120'],
            'fields.model' => ['sometimes', 'nullable', 'string', 'max:120'],
            'fields.year' => ['sometimes', 'nullable', 'integer', 'min:1886', 'max:'.(now()->year + 1)],
            'fields.fuel_type' => ['sometimes', 'nullable', 'string', 'max:64'],
            'fields.mileage' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'source_document_public_id' => ['nullable', 'uuid'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
