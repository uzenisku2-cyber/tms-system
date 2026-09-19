<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Pricing\Requests\ReverseFinancialSettlementAccountingPostingRequest;
use App\Modules\Pricing\Services\FinancialSettlementAccountingPostingReversalService;
use Illuminate\Http\JsonResponse;

final class FinancialSettlementAccountingPostingReversalController extends Controller
{
    public function store(
        string $postingExecution,
        ReverseFinancialSettlementAccountingPostingRequest $request,
        FinancialSettlementAccountingPostingReversalService $service,
    ): JsonResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        $reversal = $service->reverse(
            (int) $request->attributes->get('organization_id'),
            $postingExecution,
            $actor,
            $request->validated(),
        );

        return response()->json(['data' => $reversal->load(['entries', 'events'])], 201);
    }
}
