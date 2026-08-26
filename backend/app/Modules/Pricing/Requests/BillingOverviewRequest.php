<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use App\Modules\Pricing\Models\BillingDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BillingOverviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $perPage = $this->input('per_page');

        if (is_string($perPage) && preg_match('/^[0-9]+$/', $perPage) === 1) {
            $this->merge(['per_page' => (int) $perPage]);
        }
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'period_from' => ['nullable', 'date_format:Y-m-d'],
            'period_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:period_from'],
            'document_type' => [
                'nullable',
                Rule::in([
                    BillingDocument::TYPE_CUSTOMER_INVOICE,
                    BillingDocument::TYPE_EXTERNAL_CARRIER_SETTLEMENT,
                    BillingDocument::TYPE_DRIVER_REMUNERATION,
                ]),
            ],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
