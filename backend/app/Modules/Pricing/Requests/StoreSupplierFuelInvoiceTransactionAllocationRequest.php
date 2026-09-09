<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSupplierFuelInvoiceTransactionAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compensation.manage') === true;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'fuel_transaction_public_id' => ['required', 'uuid'],
            'allocated_amount' => ['required', 'string', 'regex:/^(0|[1-9][0-9]*)(\.[0-9]{1,2})?$/'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
