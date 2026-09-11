<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pricing\Requests\EvaluateSupplierFuelInvoiceRebillingCoverageRequest;
use App\Modules\Pricing\Services\SupplierFuelInvoiceRebillingCoverageService;
use Illuminate\Http\JsonResponse;

final class SupplierFuelInvoiceRebillingCoverageController extends Controller
{
    public function store(
        EvaluateSupplierFuelInvoiceRebillingCoverageRequest $request,
        string $supplierFuelInvoice,
        SupplierFuelInvoiceRebillingCoverageService $service,
    ): JsonResponse {
        $result = $service->evaluate(
            $supplierFuelInvoice,
            $request->validated(),
            (int) $request->attributes->get('organization_id'),
            $request->user(),
        );

        return response()->json(['data' => $result['data']], $result['replayed'] ? 200 : 201);
    }
}
