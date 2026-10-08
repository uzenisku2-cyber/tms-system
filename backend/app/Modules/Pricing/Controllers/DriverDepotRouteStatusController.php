<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Modules\Pricing\Services\CarrierDepotRouteStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DriverDepotRouteStatusController
{
    public function index(Request $request, OrganizationContext $context, CarrierDepotRouteStatusService $service): JsonResponse
    {
        return response()->json(['data' => $service->forDriver(
            (int) $request->user()->getAuthIdentifier(),
            $context->requireId(),
        )]);
    }
}
