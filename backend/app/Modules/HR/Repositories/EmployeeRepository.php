<?php

declare(strict_types=1);

namespace App\Modules\HR\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\HR\Models\Employee;

class EmployeeRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Employee);
    }
}
