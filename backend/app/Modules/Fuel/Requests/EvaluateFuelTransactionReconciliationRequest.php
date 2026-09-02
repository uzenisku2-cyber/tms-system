<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class EvaluateFuelTransactionReconciliationRequest extends FormRequest
{
    public function rules(): array
    {
        return ['expected_revision' => ['required', 'integer', 'min:0']];
    }
}
