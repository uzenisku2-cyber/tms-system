<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Fuel\Requests\EvaluateFuelTransactionReconciliationRequest;
use App\Modules\Fuel\Requests\StoreFuelTransactionReconciliationDecisionRequest;
use App\Modules\Fuel\Services\FuelTransactionReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

final class FuelTransactionReconciliationController
{
    public function show(FuelTransaction $fuelTransaction, OrganizationContext $context, FuelTransactionReconciliationService $service): JsonResponse
    {
        return response()->json(['data' => $service->show($fuelTransaction, $context->requireId())]);
    }

    public function evaluate(EvaluateFuelTransactionReconciliationRequest $request, FuelTransaction $fuelTransaction, OrganizationContext $context, FuelTransactionReconciliationService $service): JsonResponse
    {
        return response()->json(['data' => $service->evaluate($fuelTransaction, $context->requireId(), $this->actor($request), (int) $request->validated('expected_revision'))], 201);
    }

    public function decide(StoreFuelTransactionReconciliationDecisionRequest $request, FuelTransaction $fuelTransaction, OrganizationContext $context, FuelTransactionReconciliationService $service): JsonResponse
    {
        return response()->json(['data' => $service->decide($fuelTransaction, $context->requireId(), $this->actor($request), (int) $request->validated('expected_revision'), (string) $request->validated('decision_code'), $request->validated('daily_report_id') === null ? null : (int) $request->validated('daily_report_id'), (string) $request->validated('reason'))], 201);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        if (! $actor instanceof User || ! $actor->exists) {
            throw new LogicException('The reconciliation actor must be persisted.');
        }

        return $actor;
    }
}
