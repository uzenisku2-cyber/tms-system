<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleIncidentEvidenceRequest extends FormRequest
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
            'incident_type' => ['required', Rule::in(['accident', 'damage', 'theft', 'vandalism', 'breakdown', 'other'])],
            'occurred_at' => ['required', 'date'],
            'reported_at' => ['required', 'date', 'after_or_equal:occurred_at'],
            'resolved_at' => ['nullable', 'date', 'after_or_equal:occurred_at'],
            'status' => ['required', Rule::in(['reported', 'investigating', 'repair_in_progress', 'resolved', 'closed', 'rejected'])],
            'severity' => ['required', Rule::in(['minor', 'major', 'critical', 'total_loss'])],
            'driver_user_id' => ['nullable', 'integer', 'min:1'],
            'responsible_organization_id' => ['nullable', 'integer', 'min:1'],
            'location' => ['nullable', 'string', 'max:255'],
            'police_reference' => ['nullable', 'string', 'max:255'],
            'insurance_claim_reference' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
