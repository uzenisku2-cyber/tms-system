<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreDriverQualityProfileVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'change_reason' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
