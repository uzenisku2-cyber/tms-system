<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Fleet\Requests\PrepareVehicleCostAllocationFinancialHandoffRequest;
use App\Modules\Fleet\Services\VehicleCostAllocationFinancialHandoffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class VehicleCostAllocationFinancialHandoffController extends Controller
{
    public function __construct(private readonly VehicleCostAllocationFinancialHandoffService $service) {}

    public function prepare(PrepareVehicleCostAllocationFinancialHandoffRequest $request, OrganizationContext $context, string $allocationUid): JsonResponse
    {
        return response()->json($this->service->prepare($allocationUid, (int) $request->validated('expected_revision'), $context->requireId(), $this->actor($request)), 201);
    }

    public function show(Request $request, OrganizationContext $context, string $allocationUid): JsonResponse
    {
        return response()->json($this->service->show($allocationUid, $context->requireId(), $this->actor($request)));
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            abort(401);
        }

        return $actor;
    }
}
