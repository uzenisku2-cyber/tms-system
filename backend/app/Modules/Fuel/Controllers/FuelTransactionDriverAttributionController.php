<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Fuel\Requests\StoreFuelTransactionDriverAttributionRequest;
use App\Modules\Fuel\Services\FuelTransactionDriverAttributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

final class FuelTransactionDriverAttributionController
{
    public function show(FuelTransaction $fuelTransaction, OrganizationContext $context, FuelTransactionDriverAttributionService $service): JsonResponse
    { return response()->json(['data' => $service->show($fuelTransaction, $context->requireId())]); }

    public function eligibleDrivers(Request $request, FuelTransaction $fuelTransaction, OrganizationContext $context, FuelTransactionDriverAttributionService $service): JsonResponse
    { return response()->json(['data' => ['items' => $service->eligibleDrivers($fuelTransaction, $context->requireId(), $this->actor($request))]]); }

    public function store(StoreFuelTransactionDriverAttributionRequest $request, FuelTransaction $fuelTransaction, OrganizationContext $context, FuelTransactionDriverAttributionService $service): JsonResponse
    {
        return response()->json(['data' => $service->correct($fuelTransaction, $context->requireId(), $this->actor($request), (int) $request->validated('driver_id'), (int) $request->validated('expected_revision'), (string) $request->validated('reason'))], 201);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        if (! $actor instanceof User || ! $actor->exists) { throw new LogicException('The fuel attribution actor must be persisted.'); }
        return $actor;
    }
}
