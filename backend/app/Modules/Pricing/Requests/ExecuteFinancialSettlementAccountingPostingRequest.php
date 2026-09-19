<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ExecuteFinancialSettlementAccountingPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compensation.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'expected_handoff_revision' => ['required', 'integer', 'min:1'],
            'debit_account_code' => ['required', 'string', 'max:64', 'different:credit_account_code'],
            'credit_account_code' => ['required', 'string', 'max:64', 'different:debit_account_code'],
            'description' => ['required', 'string', 'max:1000'],
        ];
    }
}
