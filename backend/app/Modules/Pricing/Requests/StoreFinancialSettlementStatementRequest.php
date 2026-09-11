<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use App\Modules\Pricing\Models\FinancialSettlementStatement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreFinancialSettlementStatementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'recipient_type' => ['required', Rule::in(FinancialSettlementStatement::RECIPIENT_TYPES)],
            'recipient_organization_id' => ['nullable', 'integer', 'min:1', 'required_if:recipient_type,organization'],
            'recipient_driver_id' => ['nullable', 'integer', 'min:1', 'required_if:recipient_type,driver'],
            'period_from' => ['required', 'date_format:Y-m-d'],
            'period_until' => ['required', 'date_format:Y-m-d', 'after_or_equal:period_from'],
            'currency' => ['required', 'string', 'size:3'],
            'financial_calculation_public_ids' => ['present', 'array'],
            'financial_calculation_public_ids.*' => ['uuid', 'distinct'],
            'financial_mutual_charge_public_ids' => ['present', 'array'],
            'financial_mutual_charge_public_ids.*' => ['uuid', 'distinct'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
