<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreVehicleResponsibilityRequest extends FormRequest
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
            'responsibility_type' => ['required', Rule::in(['registered_operator', 'operational_organization', 'custodian', 'authorized_user', 'default_driver'])],
            'party_type' => ['required', Rule::in(['organization', 'user', 'external_party'])],
            'party_organization_id' => ['nullable', 'integer', 'required_if:party_type,organization', 'exists:organizations,id'],
            'party_user_id' => ['nullable', 'integer', 'required_if:party_type,user', 'exists:users,id'],
            'external_party_name' => ['nullable', 'string', 'max:255', 'required_if:party_type,external_party'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
