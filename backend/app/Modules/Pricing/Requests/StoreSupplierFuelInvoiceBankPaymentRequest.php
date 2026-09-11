<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSupplierFuelInvoiceBankPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compensation.manage') === true;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'bank_transaction_evidence_public_id' => ['required', 'uuid'],
            'expected_bank_transaction_evidence_revision' => ['required', 'integer', 'min:1'],
            'allocated_amount' => ['required', 'string', 'regex:/^\d+\.\d{2}$/'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
