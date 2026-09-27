<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleServiceEvidenceRequest extends FormRequest
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
            'source_document_public_id' => ['required', 'uuid'],
            'service_type' => ['required', Rule::in(['scheduled', 'repair', 'inspection', 'tyres', 'recall', 'other'])],
            'status' => ['required', Rule::in(['planned', 'in_progress', 'completed', 'cancelled'])],
            'summary' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:10000'],
            'opened_at' => ['required', 'date'],
            'completed_at' => ['nullable', 'date', 'after_or_equal:opened_at'],
            'next_service_on' => ['nullable', 'date'],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'next_service_odometer' => ['nullable', 'integer', 'min:0', 'gte:odometer'],
            'provider_organization_id' => ['nullable', 'integer', 'min:1'],
            'external_provider_name' => ['nullable', 'string', 'max:255'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
