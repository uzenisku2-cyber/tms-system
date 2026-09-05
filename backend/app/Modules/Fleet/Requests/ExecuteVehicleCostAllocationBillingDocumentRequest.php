<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ExecuteVehicleCostAllocationBillingDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compensation.manage') ?? false;
    }

    public function rules(): array
    {
        return ['expected_instruction_revision' => ['required', 'integer', 'min:1'], 'idempotency_key' => ['required', 'uuid'], 'period_from' => ['required', 'date_format:Y-m-d'], 'period_until' => ['required', 'date_format:Y-m-d', 'after_or_equal:period_from'], 'description' => ['required', 'string', 'max:255'], 'vat_rate_basis_points' => ['required', 'integer', 'min:0', 'max:10000']];
    }
}
