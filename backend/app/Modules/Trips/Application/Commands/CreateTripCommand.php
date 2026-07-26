<?php

namespace App\Modules\Trips\Application\Commands;

class CreateTripCommand
{
    public function __construct(
        public int $userId,
        public array $data
    ) {}
}