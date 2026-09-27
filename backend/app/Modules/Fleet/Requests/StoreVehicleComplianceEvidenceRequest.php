<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleComplianceEvidenceRequest extends FormRequest
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
            'compliance_type' => ['required', Rule::in(['technical_inspection', 'emissions', 'registration', 'roadworthiness', 'other'])],
            'identifier' => ['nullable', 'string', 'max:255'],
            'inspected_at' => ['nullable', 'date'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'status' => ['required', Rule::in(['pending', 'valid', 'expired', 'failed', 'waived'])],
            'result' => ['nullable', Rule::in(['passed', 'failed', 'conditional', 'not_applicable'])],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'issuer_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
