<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreVehicleDocumentEvidenceRequest extends FormRequest
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
            'document_type' => ['required', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:255'],
            'storage_reference' => ['required', 'string', 'max:2048'],
            'issue_date' => ['nullable', 'date'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'access_classification' => ['required', Rule::in(['operational', 'restricted', 'financial', 'private'])],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
