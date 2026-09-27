<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleFinancingEvidenceRequest extends FormRequest
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
            'financing_type' => ['required', Rule::in(['operating_lease', 'finance_lease', 'purchase_installment', 'loan', 'other'])],
            'financier_organization_id' => ['nullable', 'integer', 'min:1'],
            'external_financier_name' => ['nullable', 'string', 'max:255'],
            'debtor_type' => ['required', Rule::in(['organization', 'driver'])],
            'debtor_organization_id' => ['nullable', 'integer', 'min:1'],
            'debtor_user_id' => ['nullable', 'integer', 'min:1'],
            'agreement_number' => ['nullable', 'string', 'max:255'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'total_amount' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'initial_payment_amount' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'residual_value_amount' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'status' => ['required', Rule::in(['draft', 'active', 'suspended', 'completed', 'terminated', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:10000'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
