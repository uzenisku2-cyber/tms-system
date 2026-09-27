<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

final class ReviseVehicleInsuranceEvidenceRequest extends StoreVehicleInsuranceEvidenceRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'expected_insurance_revision' => ['required', 'integer', 'min:1'],
        ]);
    }
}
