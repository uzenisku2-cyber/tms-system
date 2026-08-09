<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Requests;

use App\Modules\DailyReports\Models\DailyReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DailyReportIndexRequest extends FormRequest
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
            'status' => [
                'nullable',
                'string',
                Rule::in(DailyReport::STATUSES),
            ],
            'status_group' => [
                'nullable',
                'string',
                Rule::in([
                    'written',
                    'waiting',
                    'correction',
                    'corrected',
                    'approved',
                    'closed',
                ]),
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
            'sort_by' => [
                'nullable',
                'string',
                Rule::in([
                    'service_date',
                    'route_number',
                    'status',
                    'created_at',
                ]),
            ],
            'sort_dir' => [
                'nullable',
                'string',
                Rule::in([
                    'asc',
                    'desc',
                ]),
            ],
            'per_page' => [
                'nullable',
                'integer',
                'between:1,100',
            ],
        ];
    }
}
