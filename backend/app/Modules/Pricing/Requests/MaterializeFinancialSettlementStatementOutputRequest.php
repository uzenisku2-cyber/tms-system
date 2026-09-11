<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class MaterializeFinancialSettlementStatementOutputRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'expected_revision' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
            'document_number' => ['nullable', 'string', 'max:64'],
            'variable_symbol' => ['nullable', 'string', 'max:32'],
            'issued_on' => ['nullable', 'date_format:Y-m-d'],
            'taxable_supply_on' => ['nullable', 'date_format:Y-m-d'],
            'due_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:issued_on'],
            'counterparty_name' => ['nullable', 'string', 'max:255'],
            'counterparty_registration_number' => ['nullable', 'string', 'max:64'],
            'counterparty_vat_number' => ['nullable', 'string', 'max:64'],
            'counterparty_account_identifier' => ['nullable', 'string', 'max:128'],
            'vat_treatment' => ['nullable', Rule::in(['standard', 'not_applicable'])],
            'vat_rate_basis_points' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'net_amount' => ['nullable', 'regex:/^\d+\.\d{2}$/'],
            'vat_amount' => ['nullable', 'regex:/^\d+\.\d{2}$/'],
        ];
    }
}
