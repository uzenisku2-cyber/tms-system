<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReverseFuelTransactionSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['expected_revision' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'min:3', 'max:1000']];
    }
}
