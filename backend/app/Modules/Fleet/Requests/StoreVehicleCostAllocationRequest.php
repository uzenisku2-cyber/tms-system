<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreVehicleCostAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'min:1'],
            'source_type' => ['required', Rule::in(['service', 'incident', 'insurance', 'provision', 'rental', 'leasing', 'installment', 'manual', 'other'])],
            'source_reference_uid' => ['nullable', 'uuid'],
            'source_document_reference' => ['nullable', 'string', 'max:255'],
            'occurred_on' => ['required', 'date_format:Y-m-d'],
            'description' => ['required', 'string', 'max:4000'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.cost_component' => ['required', Rule::in(['base_cost', 'vat', 'deductible', 'damage', 'rental', 'leasing', 'installment', 'insurance_recovery', 'other'])],
            'lines.*.responsible_party_type' => ['required', Rule::in(['organization', 'driver', 'insurer', 'state', 'internal', 'external_party'])],
            'lines.*.responsible_organization_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.responsible_user_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.external_party_name' => ['nullable', 'string', 'max:255'],
            'lines.*.net_amount' => ['required', 'decimal:0,2', 'min:0'],
            'lines.*.vat_amount' => ['required', 'decimal:0,2', 'min:0'],
            'lines.*.gross_amount' => ['required', 'decimal:0,2', 'min:0'],
            'lines.*.settlement_mode' => ['required', Rule::in(['invoice_required', 'deposit_offset', 'repair_fund_reserve', 'insurance_recovery', 'state_recovery', 'informational_only', 'manual_review'])],
            'lines.*.vat_treatment' => ['required', Rule::in(['standard_rate', 'outside_scope', 'not_applicable', 'pending_review'])],
            'lines.*.vat_rate_basis_points' => ['nullable', 'integer', 'between:0,10000'],
            'lines.*.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
