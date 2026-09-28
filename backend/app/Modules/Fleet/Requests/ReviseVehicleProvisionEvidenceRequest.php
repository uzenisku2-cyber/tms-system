<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

class ReviseVehicleProvisionEvidenceRequest extends StoreVehicleProvisionEvidenceRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return parent::rules() + ['expected_provision_revision' => ['required', 'integer', 'min:1']];
    }
}
