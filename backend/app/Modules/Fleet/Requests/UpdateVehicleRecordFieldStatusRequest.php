<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateVehicleRecordFieldStatusRequest extends FormRequest
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
            'status' => ['required', Rule::in(['missing', 'pending_document', 'unverified', 'verified', 'not_applicable'])],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
