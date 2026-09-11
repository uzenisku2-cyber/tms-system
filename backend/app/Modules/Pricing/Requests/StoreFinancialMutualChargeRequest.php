<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\FinancialMutualCharge;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreFinancialMutualChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'counterparty_type' => ['required', Rule::in(FinancialMutualCharge::PARTY_TYPES)],
            'counterparty_organization_id' => ['nullable', 'integer', 'min:1', 'required_if:counterparty_type,organization'],
            'counterparty_driver_id' => ['nullable', 'integer', 'min:1', 'required_if:counterparty_type,driver'],
            'direction' => ['required', Rule::in(FinancialMutualCharge::DIRECTIONS)],
            'category' => ['required', Rule::in(FinancialMutualCharge::CATEGORIES)],
            'description' => ['required', 'string', 'max:500'],
            'service_period_from' => ['required', 'date_format:Y-m-d'],
            'service_period_until' => ['required', 'date_format:Y-m-d', 'after_or_equal:service_period_from'],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'vat_treatment' => ['required', Rule::in([BillingDocument::VAT_STANDARD, BillingDocument::VAT_NOT_APPLICABLE])],
            'offset_eligible' => ['required', 'boolean'],
            'source_type' => ['required', 'string', 'max:100'],
            'source_public_id' => ['required', 'uuid'],
            'source_snapshot' => ['required', 'array'],
        ];
    }
}
