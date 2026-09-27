<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

final class ReviseVehicleComplianceEvidenceRequest extends StoreVehicleComplianceEvidenceRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'expected_compliance_revision' => ['required', 'integer', 'min:1'],
        ]);
    }
}
