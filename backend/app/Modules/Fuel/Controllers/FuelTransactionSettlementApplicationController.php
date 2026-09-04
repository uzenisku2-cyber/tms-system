<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Fuel\Requests\ApplyFuelTransactionSettlementRequest;
use App\Modules\Fuel\Requests\AttachFuelTransactionSettlementFinancialCalculationRequest;
use App\Modules\Fuel\Requests\ReverseFuelTransactionSettlementRequest;
use App\Modules\Fuel\Services\FuelTransactionSettlementApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

final class FuelTransactionSettlementApplicationController
{
    public function show(FuelTransaction $fuelTransaction, OrganizationContext $context, FuelTransactionSettlementApplicationService $service): JsonResponse
    {
        return response()->json(['data' => $service->show($fuelTransaction, $context->requireId())]);
    }

    public function apply(ApplyFuelTransactionSettlementRequest $request, FuelTransaction $fuelTransaction, OrganizationContext $context, FuelTransactionSettlementApplicationService $service): JsonResponse
    {
        return response()->json(['data' => $service->apply($fuelTransaction, $context->requireId(), $this->actor($request), (int) $request->validated('expected_eligibility_revision'))], 201);
    }

    public function attachFinancialCalculation(AttachFuelTransactionSettlementFinancialCalculationRequest $request, FuelTransaction $fuelTransaction, OrganizationContext $context, FuelTransactionSettlementApplicationService $service): JsonResponse
    {
        return response()->json(['data' => $service->attachFinancialCalculation($fuelTransaction, $context->requireId(), $this->actor($request), (int) $request->validated('expected_revision'), (string) $request->validated('financial_calculation_public_id'))]);
    }

    public function reverse(ReverseFuelTransactionSettlementRequest $request, FuelTransaction $fuelTransaction, OrganizationContext $context, FuelTransactionSettlementApplicationService $service): JsonResponse
    {
        return response()->json(['data' => $service->reverse($fuelTransaction, $context->requireId(), $this->actor($request), (int) $request->validated('expected_revision'), (string) $request->validated('reason'))], 201);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            throw new LogicException('The settlement application actor must be persisted.');
        }

        return $actor;
    }
}
