<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSupplierFuelInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compensation.manage') ?? false;
    }

    public function rules(): array
    {
        $money = ['required', 'regex:/^(?:0|[1-9][0-9]{0,13})(?:\.[0-9]{1,2})?$/D'];

        return [
            'idempotency_key' => ['required', 'uuid'],
            'document_number' => ['required', 'string', 'max:64'],
            'variable_symbol' => ['nullable', 'string', 'max:32', 'regex:/^[0-9]+$/D'],
            'issued_on' => ['required', 'date_format:Y-m-d'],
            'taxable_supply_on' => ['nullable', 'date_format:Y-m-d'],
            'due_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:issued_on'],
            'counterparty_name' => ['required', 'string', 'max:255'],
            'counterparty_registration_number' => ['nullable', 'string', 'max:32'],
            'counterparty_vat_number' => ['nullable', 'string', 'max:32'],
            'counterparty_account_identifier' => ['nullable', 'string', 'max:128'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'description' => ['required', 'string', 'max:255'],
            'net_amount' => $money,
            'vat_rate_basis_points' => ['required', 'integer', 'min:0', 'max:10000'],
            'vat_amount' => $money,
            'gross_amount' => $money,
        ];
    }
}
