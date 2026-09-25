<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreVehicleOwnershipRequest extends FormRequest
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
            'owner_type' => ['required', Rule::in(['organization', 'user', 'external_party'])],
            'owner_organization_id' => ['nullable', 'integer', 'required_if:owner_type,organization', 'exists:organizations,id'],
            'owner_user_id' => ['nullable', 'integer', 'required_if:owner_type,user', 'exists:users,id'],
            'external_owner_name' => ['nullable', 'string', 'max:255', 'required_if:owner_type,external_party'],
            'ownership_share_basis_points' => ['required', 'integer', 'min:1', 'max:10000'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'acquisition_basis' => ['nullable', 'string', 'max:64'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
