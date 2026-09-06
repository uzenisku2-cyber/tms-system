<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PrepareVehicleCostAllocationBankMatchingHandoffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compensation.manage') ?? false;
    }

    public function rules(): array
    {
        return ['expected_instruction_revision' => ['required', 'integer', 'min:1'], 'idempotency_key' => ['required', 'uuid'], 'bank_transaction_reference' => ['required', 'string', 'max:191'], 'bank_statement_reference' => ['nullable', 'string', 'max:191'], 'booked_at' => ['required', 'date_format:Y-m-d'], 'evidence_amount' => ['required', 'numeric', 'gt:0'], 'currency' => ['required', 'string', 'size:3'], 'counterparty_name' => ['nullable', 'string', 'max:255'], 'evidence_note' => ['required', 'string', 'max:1000']];
    }
}
