<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Fuel\Models\FuelCard;
use App\Modules\Fuel\Models\FuelCardAssignment;
use App\Modules\Fuel\Requests\AssignFuelCardRequest;
use App\Modules\Fuel\Requests\ChangeFuelCardStatusRequest;
use App\Modules\Fuel\Requests\EndFuelCardAssignmentRequest;
use App\Modules\Fuel\Requests\StoreFuelCardRequest;
use App\Modules\Fuel\Requests\StoreFuelCardSettlementPolicyRequest;
use App\Modules\Fuel\Requests\UpdateFuelCardRequest;
use App\Modules\Fuel\Services\FuelCardManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

final class FuelCardController
{
    public function index(OrganizationContext $context, FuelCardManagementService $service): JsonResponse
    {
        return response()->json(['data' => ['items' => $service->visibleCards($context->requireId())]]);
    }

    public function show(FuelCard $fuelCard, OrganizationContext $context): JsonResponse
    {
        $organizationId = $context->requireId();
        $visible = (int) $fuelCard->owner_organization_id === $organizationId || $fuelCard->assignments()->where('responsible_organization_id', $organizationId)->exists();
        if (! $visible) {
            abort(404);
        }

        return response()->json(['data' => $fuelCard->load(['assignments', 'settlementPolicies', 'events'])]);
    }

    public function store(StoreFuelCardRequest $request, OrganizationContext $context, FuelCardManagementService $service): JsonResponse
    {
        return response()->json(['data' => $service->create($context->requireId(), $this->actor($request), $request->validated())], 201);
    }

    public function update(UpdateFuelCardRequest $request, FuelCard $fuelCard, OrganizationContext $context, FuelCardManagementService $service): JsonResponse
    {
        return response()->json(['data' => $service->update($fuelCard, $context->requireId(), $this->actor($request), $request->validated())]);
    }

    public function changeStatus(ChangeFuelCardStatusRequest $request, FuelCard $fuelCard, OrganizationContext $context, FuelCardManagementService $service): JsonResponse
    {
        return response()->json(['data' => $service->changeStatus($fuelCard, $context->requireId(), $this->actor($request), (string) $request->validated('status'), (int) $request->validated('lock_version'), (string) $request->validated('reason'))]);
    }

    public function assign(AssignFuelCardRequest $request, FuelCard $fuelCard, OrganizationContext $context, FuelCardManagementService $service): JsonResponse
    {
        return response()->json(['data' => $service->assign($fuelCard, $context->requireId(), $this->actor($request), $request->validated())], 201);
    }

    public function endAssignment(EndFuelCardAssignmentRequest $request, FuelCard $fuelCard, FuelCardAssignment $assignment, OrganizationContext $context, FuelCardManagementService $service): JsonResponse
    {
        return response()->json(['data' => $service->endAssignment($fuelCard, $assignment, $context->requireId(), $this->actor($request), (string) $request->validated('valid_until'), (string) $request->validated('reason'))]);
    }

    public function storePolicy(StoreFuelCardSettlementPolicyRequest $request, FuelCard $fuelCard, OrganizationContext $context, FuelCardManagementService $service): JsonResponse
    {
        return response()->json(['data' => $service->createPolicy($fuelCard, $context->requireId(), $this->actor($request), $request->validated())], 201);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        if (! $actor instanceof User || ! $actor->exists) {
            throw new LogicException('The fuel-card actor must be persisted.');
        }

        return $actor;
    }
}
