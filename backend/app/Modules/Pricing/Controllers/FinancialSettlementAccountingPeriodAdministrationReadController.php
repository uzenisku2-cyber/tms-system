<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pricing\Requests\IndexFinancialSettlementAccountingPeriodAdministrationRequest;
use App\Modules\Pricing\Services\FinancialSettlementAccountingPeriodAdministrationReadService;
use Illuminate\Http\JsonResponse;

final class FinancialSettlementAccountingPeriodAdministrationReadController extends Controller
{
    public function index(
        IndexFinancialSettlementAccountingPeriodAdministrationRequest $request,
        FinancialSettlementAccountingPeriodAdministrationReadService $service,
    ): JsonResponse {
        $page = $service->index(
            (int) $request->attributes->get('organization_id'),
            $request->validated(),
        );

        return response()->json([
            'data' => $page->items(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function show(
        string $accountingPeriod,
        IndexFinancialSettlementAccountingPeriodAdministrationRequest $request,
        FinancialSettlementAccountingPeriodAdministrationReadService $service,
    ): JsonResponse {
        return response()->json([
            'data' => $service->show(
                (int) $request->attributes->get('organization_id'),
                $accountingPeriod,
            ),
        ]);
    }
}
