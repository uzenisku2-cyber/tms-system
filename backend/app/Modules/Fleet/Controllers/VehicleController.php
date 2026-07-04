<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Controllers;

use App\Core\Http\BaseController;
use App\Modules\Fleet\Services\VehicleService;
use Illuminate\Http\JsonResponse;

class VehicleController extends BaseController
{
    public function __construct(
        protected VehicleService $service,
    ) {}

    public function index(): JsonResponse
    {
        return $this->success([]);
    }
}
