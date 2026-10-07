<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ApproveDepotRouteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'daily_report_public_id' => ['required', 'uuid'],
            'expected_report_version' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }
}
