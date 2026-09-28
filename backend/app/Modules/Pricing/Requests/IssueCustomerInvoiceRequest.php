<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class IssueCustomerInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'document_number' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9][A-Za-z0-9._\\/-]*$/'],
            'variable_symbol' => ['nullable', 'regex:/^[0-9]{1,10}$/'],
            'issued_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'taxable_supply_on' => ['nullable', 'date_format:Y-m-d'],
            'due_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:issued_on'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
