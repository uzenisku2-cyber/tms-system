<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class MapDepotSourceDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'source_driver_name' => [
                'required',
                'string',
                'max:255',
            ],
            'driver_id' => [
                'required',
                'integer',
                'min:1',
            ],
            'expected_lock_version' => [
                'required',
                'integer',
                'min:1',
            ],
            'reason' => [
                'required',
                'string',
                'max:2000',
            ],
        ];
    }
}
