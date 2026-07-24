<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Events;

use App\Modules\Fleet\Models\Vehicle;

class VehicleCreated
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public Vehicle $vehicle
    ) {}
}