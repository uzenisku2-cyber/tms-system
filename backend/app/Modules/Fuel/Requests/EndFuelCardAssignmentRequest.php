<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class EndFuelCardAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['valid_until' => ['required', 'date'], 'reason' => ['required', 'string', 'min:3', 'max:1000']];
    }
}
