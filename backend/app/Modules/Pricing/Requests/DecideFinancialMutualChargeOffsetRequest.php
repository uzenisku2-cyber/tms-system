<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DecideFinancialMutualChargeOffsetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'expected_revision' => ['required', 'integer', 'min:1'],
            'decision' => ['required', 'in:accepted,rejected'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
