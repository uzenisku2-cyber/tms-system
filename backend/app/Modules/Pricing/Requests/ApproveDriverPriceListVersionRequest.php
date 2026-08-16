<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ApproveDriverPriceListVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'expected_lock_version' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }
}
