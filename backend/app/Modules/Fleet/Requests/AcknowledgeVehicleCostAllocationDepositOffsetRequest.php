<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AcknowledgeVehicleCostAllocationDepositOffsetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compensation.manage') ?? false;
    }

    public function rules(): array
    {
        return ['expected_instruction_revision' => ['required', 'integer', 'min:1'], 'idempotency_key' => ['required', 'uuid'], 'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'card', 'other'])], 'payment_reference' => ['nullable', 'string', 'max:120'], 'evidence_note' => ['required', 'string', 'max:1000'], 'vat_disposition' => ['required', Rule::in(['offset', 'repair_fund_pending'])]];
    }
}
