<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CorrectFinancialSettlementAccountingPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compensation.manage') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'expected_revision' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:1000'],
            'posting_date' => ['required', 'date_format:Y-m-d'],
            'accounting_reference' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'entries' => ['required', 'array', 'min:2', 'max:100'],
            'entries.*.side' => ['required', Rule::in(['debit', 'credit'])],
            'entries.*.account_code' => ['required', 'string', 'max:64'],
            'entries.*.amount_minor' => ['required', 'integer', 'min:1'],
            'entries.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
