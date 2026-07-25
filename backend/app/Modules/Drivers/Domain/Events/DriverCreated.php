<?php

namespace App\Modules\Drivers\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $driverId,
    ) {}
}
