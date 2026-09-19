<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Pricing\Requests\CorrectFinancialSettlementAccountingPostingRequest;
use App\Modules\Pricing\Services\FinancialSettlementAccountingPostingCorrectionService;
use Illuminate\Http\JsonResponse;

final class FinancialSettlementAccountingPostingCorrectionController extends Controller
{
    public function store(
        string $postingExecution,
        CorrectFinancialSettlementAccountingPostingRequest $request,
        FinancialSettlementAccountingPostingCorrectionService $service,
    ): JsonResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        $correction = $service->correct(
            (int) $request->attributes->get('organization_id'),
            $postingExecution,
            $actor,
            $request->validated(),
        );

        return response()->json(['data' => $correction->load(['originalExecution', 'reversal', 'replacementExecution.entries', 'events'])], 201);
    }
}
