<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Fleet\Requests\AcknowledgeVehicleCostAllocationDepositOffsetRequest;
use App\Modules\Fleet\Services\VehicleCostAllocationDepositOffsetService;
use Illuminate\Http\JsonResponse;

final class VehicleCostAllocationDepositOffsetController extends Controller
{
    public function __construct(private readonly VehicleCostAllocationDepositOffsetService $service) {}

    public function acknowledge(AcknowledgeVehicleCostAllocationDepositOffsetRequest $request, OrganizationContext $context, string $instructionPublicId): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            abort(401);
        }

        return response()->json($this->service->acknowledge($instructionPublicId, $request->validated(), $context->requireId(), $actor), 201);
    }
}
