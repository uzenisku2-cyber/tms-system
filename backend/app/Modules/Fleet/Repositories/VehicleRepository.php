<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Fleet\Models\Vehicle;

class VehicleRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Vehicle());
    }
}
