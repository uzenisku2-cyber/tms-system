<?php

namespace App\Modules\Fleet\Domain\Events;

class VehicleCreated
{
    public function __construct(
        public int $vehicleId,
        public array $payload = []
    ) {}
}