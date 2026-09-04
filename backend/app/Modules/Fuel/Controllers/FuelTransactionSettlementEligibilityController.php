<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Fuel\Requests\EvaluateFuelTransactionSettlementEligibilityRequest;
use App\Modules\Fuel\Services\FuelTransactionSettlementEligibilityService;
use Illuminate\Http\JsonResponse;
use LogicException;

final class FuelTransactionSettlementEligibilityController
{
    public function show(FuelTransaction $fuelTransaction, OrganizationContext $context, FuelTransactionSettlementEligibilityService $service): JsonResponse
    {
        return response()->json(['data' => $service->show($fuelTransaction, $context->requireId())]);
    }

    public function evaluate(EvaluateFuelTransactionSettlementEligibilityRequest $request, FuelTransaction $fuelTransaction, OrganizationContext $context, FuelTransactionSettlementEligibilityService $service): JsonResponse
    {
        return response()->json(['data' => $service->evaluate($fuelTransaction, $context->requireId(), $this->actor($request), (int) $request->validated('expected_revision'))]);
    }

    private function actor(EvaluateFuelTransactionSettlementEligibilityRequest $request): User
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            throw new LogicException('The settlement eligibility actor must be persisted.');
        }

        return $actor;
    }
}
