<?php

namespace App\Modules\Drivers\Application\Commands;

class CreateDriverCommand
{
    public function __construct(
        public int $userId,
        public array $data
    ) {}
}