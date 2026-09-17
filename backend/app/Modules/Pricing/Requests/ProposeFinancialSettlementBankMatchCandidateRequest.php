<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ProposeFinancialSettlementBankMatchCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compensation.manage') === true;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'minimum_score_basis_points' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'date_window_days' => ['sometimes', 'integer', 'min:0', 'max:366'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
