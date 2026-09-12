<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pricing\Requests\ReverseSupplierFuelInvoiceBankPaymentRequest;
use App\Modules\Pricing\Requests\StoreSupplierFuelInvoiceBankPaymentRequest;
use App\Modules\Pricing\Services\SupplierFuelInvoiceBankPaymentService;
use Illuminate\Http\JsonResponse;

final class SupplierFuelInvoiceBankPaymentController extends Controller
{
    public function store(
        StoreSupplierFuelInvoiceBankPaymentRequest $request,
        string $supplierFuelInvoice,
        SupplierFuelInvoiceBankPaymentService $service,
    ): JsonResponse {
        $result = $service->store(
            $supplierFuelInvoice,
            $request->validated(),
            (int) $request->attributes->get('organization_id'),
            $request->user(),
        );

        return response()->json(['data' => $result['data']], $result['replayed'] ? 200 : 201);
    }

    public function reverse(
        ReverseSupplierFuelInvoiceBankPaymentRequest $request,
        string $supplierFuelInvoice,
        string $payment,
        SupplierFuelInvoiceBankPaymentService $service,
    ): JsonResponse {
        $result = $service->reverse(
            $supplierFuelInvoice,
            $payment,
            $request->validated(),
            (int) $request->attributes->get('organization_id'),
            $request->user(),
        );

        return response()->json(['data' => $result['data']]);
    }
}
