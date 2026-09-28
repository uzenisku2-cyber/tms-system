<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AllocateCustomerInvoiceBankPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'bank_transaction_evidence_public_id' => ['required', 'uuid'],
            'expected_bank_revision' => ['required', 'integer', 'min:1'],
            'allocated_amount_minor' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
