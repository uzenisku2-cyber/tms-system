<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

final class ReviseVehicleServiceEvidenceRequest extends StoreVehicleServiceEvidenceRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'expected_service_revision' => ['required', 'integer', 'min:1'],
        ]);
    }
}
