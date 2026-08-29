<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreFuelSurchargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_relationship_id' => [
                'required',
                'integer',
                'exists:organization_relationships,id',
            ],
            'billing_rate_per_actual_km' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,4',
            ],
            'valid_from' => ['required', 'date_format:Y-m-d'],
            'valid_until' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:valid_from',
            ],
            'note' => ['nullable', 'string', 'max:1000'],
            'recipients' => ['present', 'array'],
            'recipients.*.recipient_type' => [
                'required',
                Rule::in(['own_driver', 'external_carrier']),
            ],
            'recipients.*.driver_organization_assignment_id' => [
                'nullable',
                'integer',
                'exists:driver_organization_assignments,id',
            ],
            'recipients.*.carrier_relationship_id' => [
                'nullable',
                'integer',
                'exists:organization_relationships,id',
            ],
            'recipients.*.payout_rate_per_actual_km' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,4',
            ],
            'recipients.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
