<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexFinancialSettlementAccountingPeriodAdministrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compensation.view') === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(['open', 'closed', 'reopened'])],
            'currency' => ['nullable', 'string', 'size:3'],
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
