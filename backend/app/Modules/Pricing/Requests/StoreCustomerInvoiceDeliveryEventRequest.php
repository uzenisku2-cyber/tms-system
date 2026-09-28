<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCustomerInvoiceDeliveryEventRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'expected_revision' => ['required', 'integer', 'min:0'],
            'pdf_sha256' => ['required', 'regex:/^[a-f0-9]{64}$/'],
            'method' => ['required', Rule::in(['email', 'portal', 'handover', 'post', 'other'])],
            'recipient' => ['required', 'string', 'max:255'],
            'delivered_at' => ['required', 'date', 'before_or_equal:now'],
            'evidence_reference' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
