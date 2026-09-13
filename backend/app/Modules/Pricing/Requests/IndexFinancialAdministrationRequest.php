<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexFinancialAdministrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compensation.view') === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'max:40'],
            'party_type' => ['sometimes', Rule::in(['organization', 'driver'])],
            'direction' => ['sometimes', Rule::in(['receivable', 'payable'])],
            'period_from' => ['sometimes', 'date_format:Y-m-d'],
            'period_until' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:period_from'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
