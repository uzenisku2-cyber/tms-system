<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

final class ReviseVehicleIncidentEvidenceRequest extends StoreVehicleIncidentEvidenceRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'expected_incident_revision' => ['required', 'integer', 'min:1'],
        ]);
    }
}
