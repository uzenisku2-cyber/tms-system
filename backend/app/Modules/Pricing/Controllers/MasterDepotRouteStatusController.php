<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Modules\Pricing\Services\CarrierDepotRouteStatusService;
use Illuminate\Http\JsonResponse;

final class MasterDepotRouteStatusController
{
    public function index(OrganizationContext $context, CarrierDepotRouteStatusService $service): JsonResponse
    {
        return response()->json(['data' => $service->forMaster($context->requireId())]);
    }
}
