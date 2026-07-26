<?php

namespace App\Modules\Fleet\Application\Commands;

class CreateVehicleCommand
{
    public function __construct(
        public int $userId,
        public array $data
    ) {}
}