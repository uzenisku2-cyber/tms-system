<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ExecuteVehicleCostAllocationBankMatchingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compensation.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'expected_handoff_revision' => ['required', 'integer', 'min:1'],
            'bank_transaction_evidence_public_id' => ['required', 'uuid'],
            'expected_bank_transaction_evidence_revision' => ['required', 'integer', 'min:1'],
            'idempotency_key' => ['required', 'uuid'],
            'matched_amount' => ['required', 'numeric', 'gt:0'],
            'effective_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
