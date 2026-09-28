<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SendCustomerInvoiceEmailRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'pdf_sha256' => ['required', 'regex:/^[a-f0-9]{64}$/'],
            'recipient_email' => ['required', 'email:rfc', 'max:254'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
