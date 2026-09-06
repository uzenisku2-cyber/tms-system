<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Modules\Fleet\Requests\PrepareVehicleCostAllocationBankMatchingHandoffRequest;
use App\Modules\Fleet\Services\VehicleCostAllocationBankMatchingHandoffService;
use Illuminate\Http\JsonResponse;

final class VehicleCostAllocationBankMatchingHandoffController extends Controller
{
    public function __construct(private readonly VehicleCostAllocationBankMatchingHandoffService $service) {}

    public function prepare(PrepareVehicleCostAllocationBankMatchingHandoffRequest $request, OrganizationContext $context, string $instructionPublicId): JsonResponse
    {
        $actor = $request->user();
        if ($actor === null) {
            abort(401);
        }

        return response()->json($this->service->prepare($instructionPublicId, $request->validated(), $context->requireId(), $actor), 201);
    }
}
