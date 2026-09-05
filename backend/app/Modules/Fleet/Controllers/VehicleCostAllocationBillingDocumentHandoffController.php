<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Fleet\Requests\ExecuteVehicleCostAllocationBillingDocumentRequest;
use App\Modules\Fleet\Services\VehicleCostAllocationBillingDocumentHandoffService;
use Illuminate\Http\JsonResponse;

final class VehicleCostAllocationBillingDocumentHandoffController extends Controller
{
    public function __construct(private readonly VehicleCostAllocationBillingDocumentHandoffService $service) {}

    public function execute(ExecuteVehicleCostAllocationBillingDocumentRequest $request, OrganizationContext $context, string $instructionPublicId): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            abort(401);
        }

        return response()->json($this->service->execute($instructionPublicId, $request->validated(), $context->requireId(), $actor), 201);
    }
}
