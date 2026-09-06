<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreBankTransactionEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compensation.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'source_type' => ['required', Rule::in(['manual_evidence', 'bank_import'])],
            'source_reference' => ['required', 'string', 'max:191'],
            'bank_statement_reference' => ['nullable', 'string', 'max:191'],
            'direction' => ['required', Rule::in(['credit', 'debit'])],
            'booked_at' => ['required', 'date_format:Y-m-d'],
            'value_date' => ['nullable', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['required', 'string', 'size:3'],
            'account_identifier' => ['nullable', 'string', 'max:191'],
            'counterparty_name' => ['nullable', 'string', 'max:255'],
            'counterparty_account_identifier' => ['nullable', 'string', 'max:191'],
            'variable_symbol' => ['nullable', 'string', 'max:32'],
            'message' => ['nullable', 'string', 'max:1000'],
            'evidence_note' => ['required', 'string', 'max:1000'],
        ];
    }
}
