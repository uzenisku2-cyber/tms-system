<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Pricing\Requests\MaterializeFinancialSettlementStatementOutputRequest;
use App\Modules\Pricing\Requests\StoreFinancialSettlementStatementRequest;
use App\Modules\Pricing\Requests\TransitionFinancialSettlementStatementRequest;
use App\Modules\Pricing\Services\FinancialSettlementStatementService;
use Illuminate\Http\JsonResponse;

final class FinancialSettlementStatementController extends Controller
{
    public function store(StoreFinancialSettlementStatementRequest $request, FinancialSettlementStatementService $service): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        $result = $service->store($request->validated(), (int) $request->attributes->get('organization_id'), $actor);

        return response()->json(['data' => $result['data']], $result['replayed'] ? 200 : 201);
    }

    public function transition(
        TransitionFinancialSettlementStatementRequest $request,
        string $financialSettlementStatement,
        FinancialSettlementStatementService $service,
    ): JsonResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        $result = $service->transition(
            $financialSettlementStatement,
            $request->validated(),
            (int) $request->attributes->get('organization_id'),
            $actor,
        );

        return response()->json(['data' => $result['data']]);
    }

    public function materializeOutput(
        MaterializeFinancialSettlementStatementOutputRequest $request,
        string $financialSettlementStatement,
        FinancialSettlementStatementService $service,
    ): JsonResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        $result = $service->materializeOutput(
            $financialSettlementStatement,
            $request->validated(),
            (int) $request->attributes->get('organization_id'),
            $actor,
        );

        return response()->json(['data' => $result['data']], $result['replayed'] ? 200 : 201);
    }
}
