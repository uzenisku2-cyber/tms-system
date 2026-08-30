<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreFuelTransactionDriverAttributionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'driver_id' => ['required', 'integer', 'min:1', 'exists:drivers,id'],
            'expected_revision' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
