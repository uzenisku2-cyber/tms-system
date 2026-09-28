<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

final class ReviseVehicleInstallmentScheduleRequest extends StoreVehicleInstallmentScheduleRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'expected_schedule_revision' => ['required', 'integer', 'min:1'],
        ]);
    }
}
