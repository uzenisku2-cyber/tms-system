<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReverseCustomerInvoiceBankPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'expected_revision' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
