<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleInstallmentRequest extends FormRequest
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
            'schedule_public_id' => ['required', 'uuid'],
            'expected_schedule_revision' => ['required', 'integer', 'min:1'],
            'source_document_public_id' => ['required', 'uuid'],
            'sequence_number' => ['required', 'integer', 'min:1'],
            'due_on' => ['required', 'date'],
            'principal_amount' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'finance_charge_amount' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'other_amount' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'total_amount' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'status' => ['required', Rule::in(['planned', 'cancelled', 'replaced', 'waived'])],
            'notes' => ['nullable', 'string', 'max:10000'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
