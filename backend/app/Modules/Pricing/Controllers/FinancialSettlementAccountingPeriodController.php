<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Pricing\Requests\StoreFinancialSettlementAccountingPeriodRequest;
use App\Modules\Pricing\Requests\TransitionFinancialSettlementAccountingPeriodRequest;
use App\Modules\Pricing\Services\FinancialSettlementAccountingPeriodService;
use Illuminate\Http\JsonResponse;

final class FinancialSettlementAccountingPeriodController extends Controller
{
    public function store(StoreFinancialSettlementAccountingPeriodRequest $request, FinancialSettlementAccountingPeriodService $service): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        return response()->json(['data' => $service->create((int) $request->attributes->get('organization_id'), $actor, $request->validated())->load('events')], 201);
    }

    public function close(string $accountingPeriod, TransitionFinancialSettlementAccountingPeriodRequest $request, FinancialSettlementAccountingPeriodService $service): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        return response()->json(['data' => $service->transition((int) $request->attributes->get('organization_id'), $accountingPeriod, $actor, $request->validated(), 'close')->load('events')]);
    }

    public function reopen(string $accountingPeriod, TransitionFinancialSettlementAccountingPeriodRequest $request, FinancialSettlementAccountingPeriodService $service): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        return response()->json(['data' => $service->transition((int) $request->attributes->get('organization_id'), $accountingPeriod, $actor, $request->validated(), 'reopen')->load('events')]);
    }
}
