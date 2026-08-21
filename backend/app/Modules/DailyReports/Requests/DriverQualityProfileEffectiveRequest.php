<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DriverQualityProfileEffectiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (
            [
                'driver_organization_assignment_id',
                'organization_relationship_id',
            ] as $key
        ) {
            $value = $this->query($key);

            if (is_string($value) && ctype_digit($value)) {
                $this->merge([
                    $key => (int) $value,
                ]);
            }
        }
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'service_date' => [
                'required',
                'date_format:Y-m-d',
            ],
            'driver_organization_assignment_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'organization_relationship_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }
}
