<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DriverPerformanceOverviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $driverId = $this->query(
            'performed_by_driver_id',
        );

        if (
            is_string($driverId)
            && ctype_digit($driverId)
        ) {
            $this->merge([
                'performed_by_driver_id' => (int) $driverId,
            ]);
        }

        $carrierOrganizationId = $this->query(
            'carrier_organization_id',
        );

        if (
            is_string($carrierOrganizationId)
            && ctype_digit($carrierOrganizationId)
        ) {
            $this->merge([
                'carrier_organization_id' => (int) $carrierOrganizationId,
            ]);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'performed_by_driver_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'carrier_scope' => [
                'nullable',
                'string',
                Rule::in([
                    'all',
                    'own',
                    'external',
                    'unattributed',
                ]),
            ],
            'carrier_organization_id' => [
                'nullable',
                'integer',
                'min:1',
                'required_if:carrier_scope,external',
            ],
            'period' => [
                'nullable',
                'string',
                Rule::in([
                    'current_month',
                    'previous_month',
                    'current_year',
                    'previous_year',
                    'last_12_months',
                    'all_history',
                    'custom',
                ]),
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
            'group_by' => [
                'nullable',
                'string',
                Rule::in([
                    'day',
                    'month',
                ]),
            ],
        ];
    }
}
