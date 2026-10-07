<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Pricing\Services\CarrierDepotRouteStatusService;
use Illuminate\Http\JsonResponse;

final class CarrierDepotRouteStatusController
{
    public function index(OrganizationContext $context, CarrierDepotRouteStatusService $service): JsonResponse
    {
        $carrierId = $context->requireId();
        abort_unless(Organization::query()->whereKey($carrierId)
            ->where('type', Organization::TYPE_SUBCONTRACTOR)->exists(), 403);

        return response()->json(['data' => $service->overview($carrierId)]);
    }
}
