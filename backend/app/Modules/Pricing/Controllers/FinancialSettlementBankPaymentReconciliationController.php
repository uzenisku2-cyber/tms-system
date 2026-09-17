<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Pricing\Requests\ConfirmFinancialSettlementBankPaymentReconciliationRequest;
use App\Modules\Pricing\Requests\ReopenFinancialSettlementBankPaymentReconciliationRequest;
use App\Modules\Pricing\Services\FinancialSettlementBankPaymentReconciliationService;
use Illuminate\Http\JsonResponse;

final class FinancialSettlementBankPaymentReconciliationController extends Controller
{
    public function confirm(
        ConfirmFinancialSettlementBankPaymentReconciliationRequest $request,
        string $financialSettlementStatement,
        string $payment,
        FinancialSettlementBankPaymentReconciliationService $service,
    ): JsonResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        $result = $service->confirm(
            $financialSettlementStatement, $payment, $request->validated(),
            (int) $request->attributes->get('organization_id'), $actor,
        );

        return response()->json(['data' => $result['data']], $result['created'] && ! $result['replayed'] ? 201 : 200);
    }

    public function reopen(
        ReopenFinancialSettlementBankPaymentReconciliationRequest $request,
        string $financialSettlementStatement,
        string $payment,
        FinancialSettlementBankPaymentReconciliationService $service,
    ): JsonResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        $result = $service->reopen(
            $financialSettlementStatement, $payment, $request->validated(),
            (int) $request->attributes->get('organization_id'), $actor,
        );

        return response()->json(['data' => $result['data']]);
    }
}
