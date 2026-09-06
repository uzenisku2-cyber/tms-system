<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReserveVehicleCostAllocationRepairFundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compensation.manage') ?? false;
    }

    public function rules(): array
    {
        return ['expected_instruction_revision' => ['required', 'integer', 'min:1'], 'idempotency_key' => ['required', 'uuid'], 'reserve_purpose' => ['required', Rule::in(['vehicle_repair', 'damage_deductible', 'maintenance', 'other'])], 'evidence_note' => ['required', 'string', 'max:1000']];
    }
}
