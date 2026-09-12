<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class MaterializeSupplierFuelInvoiceBankMatchCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compensation.manage') === true;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'expected_revision' => ['required', 'integer', 'min:2'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
