<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleProvisionPriceRequest extends FormRequest
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
            'expected_provision_revision' => ['required', 'integer', 'min:1'],
            'provision_public_id' => ['required', 'uuid'],
            'source_document_public_id' => ['required', 'uuid'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99', 'decimal:0,2'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'billing_period' => ['required', Rule::in(['one_time', 'daily', 'weekly', 'monthly', 'none'])],
            'billing_mode' => ['required', Rule::in(['invoice_required', 'deposit_offset', 'informational_only', 'manual_review'])],
            'vat_mode' => ['required', Rule::in(['standard_rate', 'not_applicable', 'pending_review'])],
            'vat_rate_basis_points' => ['nullable', 'integer', 'between:0,10000'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
