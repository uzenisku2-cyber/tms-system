<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleProvisionEvidenceRequest extends FormRequest
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
            'provider_type' => ['required', Rule::in(['organization', 'driver'])],
            'provider_organization_id' => ['nullable', 'integer', 'min:1'],
            'provider_user_id' => ['nullable', 'integer', 'min:1'],
            'recipient_type' => ['required', Rule::in(['organization', 'driver'])],
            'recipient_organization_id' => ['nullable', 'integer', 'min:1'],
            'recipient_user_id' => ['nullable', 'integer', 'min:1'],
            'provision_mode' => ['required', Rule::in(['own_vehicle', 'free_use', 'rental', 'operating_lease', 'finance_lease', 'purchase_installment'])],
            'agreement_number' => ['nullable', 'string', 'max:255'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'status' => ['required', Rule::in(['draft', 'active', 'suspended', 'ended', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:10000'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
