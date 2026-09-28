<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCustomerInvoiceDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'customer_organization_id' => ['required', 'integer', 'min:1'],
            'period_from' => ['required', 'date_format:Y-m-d'],
            'period_until' => ['required', 'date_format:Y-m-d', 'after_or_equal:period_from'],
            'calculation_public_ids' => ['required', 'array', 'min:1', 'max:100'],
            'calculation_public_ids.*' => ['required', 'uuid', 'distinct'],
        ];
    }
}
