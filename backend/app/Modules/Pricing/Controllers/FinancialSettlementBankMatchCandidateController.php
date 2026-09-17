<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Pricing\Requests\MaterializeFinancialSettlementBankMatchCandidateRequest;
use App\Modules\Pricing\Requests\ProposeFinancialSettlementBankMatchCandidateRequest;
use App\Modules\Pricing\Requests\ReverseFinancialSettlementBankPaymentRequest;
use App\Modules\Pricing\Requests\ReviewFinancialSettlementBankMatchCandidateRequest;
use App\Modules\Pricing\Services\FinancialSettlementBankMatchCandidateProposalService;
use App\Modules\Pricing\Services\FinancialSettlementBankPaymentService;
use Illuminate\Http\JsonResponse;

final class FinancialSettlementBankMatchCandidateController extends Controller
{
    public function propose(
        ProposeFinancialSettlementBankMatchCandidateRequest $request,
        string $financialSettlementStatement,
        FinancialSettlementBankMatchCandidateProposalService $service,
    ): JsonResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        $result = $service->propose(
            $financialSettlementStatement,
            $request->validated(),
            (int) $request->attributes->get('organization_id'),
            $actor,
        );

        return response()->json(['data' => $result['data']], $result['replayed'] ? 200 : 201);
    }

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

    public function materialize(
        MaterializeFinancialSettlementBankMatchCandidateRequest $request,
        string $financialSettlementStatement,
        string $candidate,
        FinancialSettlementBankPaymentService $service,
    ): JsonResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        $result = $service->materialize(
            $financialSettlementStatement,
            $candidate,
            $request->validated(),
            (int) $request->attributes->get('organization_id'),
            $actor,
        );

        return response()->json(['data' => $result['data']], $result['replayed'] ? 200 : 201);
    }

    public function reverse(
        ReverseFinancialSettlementBankPaymentRequest $request,
        string $financialSettlementStatement,
        string $payment,
        FinancialSettlementBankPaymentService $service,
    ): JsonResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        $result = $service->reverse(
            $financialSettlementStatement,
            $payment,
            $request->validated(),
            (int) $request->attributes->get('organization_id'),
            $actor,
        );

        return response()->json(['data' => $result['data']]);
    }
}
