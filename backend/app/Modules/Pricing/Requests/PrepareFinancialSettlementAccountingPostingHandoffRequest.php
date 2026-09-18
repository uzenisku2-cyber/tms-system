<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PrepareFinancialSettlementAccountingPostingHandoffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compensation.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'expected_payment_revision' => ['required', 'integer', 'min:1'],
            'expected_reconciliation_revision' => ['required', 'integer', 'min:1'],
            'posting_date' => ['required', 'date_format:Y-m-d'],
            'accounting_reference' => ['nullable', 'string', 'max:191'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
