<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DecideDriverAvailabilityDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:confirmed,rejected'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'expected_revision' => ['required', 'integer', 'min:1'],
        ];
    }
}
