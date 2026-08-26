<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreFuelCardSettlementPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settlement_target' => ['required', Rule::in(['carrier', 'driver'])],
            'discount_beneficiary' => ['required', Rule::in(['carrier', 'driver'])],
            'amount_basis' => ['required', Rule::in(['net', 'gross'])],
            'vat_mode' => ['required', Rule::in(['counterparty_tax_profile', 'not_applicable'])],
            'valid_from' => ['required', 'date_format:Y-m-d'],
            'valid_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:valid_from'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
