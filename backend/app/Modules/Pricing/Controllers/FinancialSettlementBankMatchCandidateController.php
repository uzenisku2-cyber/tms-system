<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Pricing\Requests\ReviewFinancialSettlementBankMatchCandidateRequest;
use App\Modules\Pricing\Services\FinancialSettlementBankMatchCandidateProposalService;
use Illuminate\Http\JsonResponse;

final class FinancialSettlementBankMatchCandidateController extends Controller
{
    public function review(
        ReviewFinancialSettlementBankMatchCandidateRequest $request,
        string $financialSettlementStatement,
        string $candidate,
        FinancialSettlementBankMatchCandidateProposalService $service,
    ): JsonResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        $result = $service->review(
            $financialSettlementStatement,
            $candidate,
            $request->validated(),
            (int) $request->attributes->get('organization_id'),
            $actor,
        );

        return response()->json(['data' => $result['data']]);
    }
}
