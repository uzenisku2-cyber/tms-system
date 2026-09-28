<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreInvoicePaymentAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'iban' => ['required', 'string', 'max:42'],
            'account_holder' => ['required', 'string', 'max:255'],
            'source_reference' => ['required', 'string', 'min:3', 'max:255'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
