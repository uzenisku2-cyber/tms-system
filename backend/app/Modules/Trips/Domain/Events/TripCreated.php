<?php

namespace App\Modules\Trips\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripCreated
{
    use Dispatchable;
    use SerializesModels;


    public function __construct(
        public int $tripId,
        public array $payload
    ) {}
}