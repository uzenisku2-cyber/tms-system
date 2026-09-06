<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Fleet\Requests\ReserveVehicleCostAllocationRepairFundRequest;
use App\Modules\Fleet\Services\VehicleCostAllocationRepairFundService;
use Illuminate\Http\JsonResponse;

final class VehicleCostAllocationRepairFundController extends Controller
{
    public function __construct(private readonly VehicleCostAllocationRepairFundService $service) {}

    public function reserve(ReserveVehicleCostAllocationRepairFundRequest $request, OrganizationContext $context, string $instructionPublicId): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            abort(401);
        }

        return response()->json($this->service->reserve($instructionPublicId, $request->validated(), $context->requireId(), $actor), 201);
    }
}
