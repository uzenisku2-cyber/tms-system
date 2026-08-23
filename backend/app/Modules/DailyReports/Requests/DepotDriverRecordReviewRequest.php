<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Requests;

use App\Modules\DailyReports\Services\DepotDriverRecordReviewService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DepotDriverRecordReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (
            [
                'performed_by_driver_id',
                'page',
                'per_page',
            ] as $key
        ) {
            $value = $this->query($key);

            if (is_string($value) && ctype_digit($value)) {
                $normalized[$key] = (int) $value;
            }
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'comparison_status' => [
                'nullable',
                'string',
                Rule::in(
                    DepotDriverRecordReviewService::COMPARISON_STATUSES,
                ),
            ],
            'performed_by_driver_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'service_date_from' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'service_date_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:service_date_from',
            ],
            'route_number' => [
                'nullable',
                'string',
                'max:100',
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'between:1,100',
            ],
        ];
    }
}
