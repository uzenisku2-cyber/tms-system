<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Modules\Fleet\Requests\ExecuteVehicleCostAllocationBankMatchingRequest;
use App\Modules\Fleet\Services\VehicleCostAllocationBankMatchingExecutionService;
use Illuminate\Http\JsonResponse;

final class VehicleCostAllocationBankMatchingExecutionController extends Controller
{
    public function __construct(private readonly VehicleCostAllocationBankMatchingExecutionService $service) {}

    public function execute(ExecuteVehicleCostAllocationBankMatchingRequest $request, OrganizationContext $context, string $handoffPublicId): JsonResponse
    {
        $actor = $request->user();
        if ($actor === null) {
            abort(401);
        }

        return response()->json($this->service->execute($handoffPublicId, $request->validated(), $context->requireId(), $actor), 201);
    }
}
