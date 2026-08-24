<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DepotImportReviewResolutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $driverRequired = $this->route()?->getActionMethod() === 'correctDriver';

        return [
            'confirmed' => ['required', 'accepted'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
            'driver_id' => [$driverRequired ? 'required' : 'nullable', 'integer', 'min:1'],
        ];
    }
}
