<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CustomerReceivablesOverviewRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['customer_organization_id', 'per_page', 'page'] as $field) {
            $value = $this->input($field);
            if (is_string($value) && preg_match('/^[0-9]+$/', $value) === 1) {
                $this->merge([$field => (int) $value]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'customer_organization_id' => ['nullable', 'integer', 'min:1'],
            'state' => ['nullable', Rule::in(['open', 'unpaid', 'partially_paid', 'paid', 'overdue'])],
            'as_of' => ['nullable', 'date_format:Y-m-d'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
