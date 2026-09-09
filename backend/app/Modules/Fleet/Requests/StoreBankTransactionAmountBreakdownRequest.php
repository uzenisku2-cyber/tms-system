<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreBankTransactionAmountBreakdownRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compensation.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'expected_revision' => ['required', 'integer', 'min:0'],
            'finalize' => ['required', 'boolean'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
            'components' => ['required', 'array', 'min:1', 'max:100'],
            'components.*.type' => ['required', Rule::in(['vat', 'deductible', 'custom'])],
            'components.*.label' => ['nullable', 'string', 'max:191'],
            'components.*.amount' => ['required', 'string', 'regex:/^-?\d{1,15}(?:[\.,]\d{1,2})?$/'],
            'components.*.metadata' => ['nullable', 'array'],
        ];
    }
}
