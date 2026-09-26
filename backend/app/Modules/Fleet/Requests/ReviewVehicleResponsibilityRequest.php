<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReviewVehicleResponsibilityRequest extends FormRequest
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
            'expected_responsibility_revision' => ['required', 'integer', 'min:1'],
            'source_document_public_id' => ['required', 'uuid'],
            'status' => ['required', Rule::in(['active', 'ended', 'cancelled'])],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
