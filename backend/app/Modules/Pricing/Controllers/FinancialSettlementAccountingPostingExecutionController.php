<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Pricing\Requests\ExecuteFinancialSettlementAccountingPostingRequest;
use App\Modules\Pricing\Services\FinancialSettlementAccountingPostingExecutionService;
use Illuminate\Http\JsonResponse;

final class FinancialSettlementAccountingPostingExecutionController extends Controller
{
    public function store(ExecuteFinancialSettlementAccountingPostingRequest $request, string $handoff, FinancialSettlementAccountingPostingExecutionService $service): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        $result = $service->execute($handoff, $request->validated(), (int) $request->attributes->get('organization_id'), $actor);

        return response()->json(['data' => $result['data']], $result['replayed'] ? 200 : 201);
    }
}
