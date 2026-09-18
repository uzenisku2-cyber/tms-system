<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Pricing\Requests\PrepareFinancialSettlementAccountingPostingHandoffRequest;
use App\Modules\Pricing\Services\FinancialSettlementAccountingPostingHandoffService;
use Illuminate\Http\JsonResponse;

final class FinancialSettlementAccountingPostingHandoffController extends Controller
{
    public function store(
        PrepareFinancialSettlementAccountingPostingHandoffRequest $request,
        string $financialSettlementStatement,
        string $payment,
        FinancialSettlementAccountingPostingHandoffService $service,
    ): JsonResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        $result = $service->prepare(
            $financialSettlementStatement,
            $payment,
            $request->validated(),
            (int) $request->attributes->get('organization_id'),
            $actor,
        );

        return response()->json(['data' => $result['data']], $result['replayed'] ? 200 : 201);
    }
}
