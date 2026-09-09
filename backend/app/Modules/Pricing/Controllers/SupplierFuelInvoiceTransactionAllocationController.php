<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pricing\Requests\ReverseSupplierFuelInvoiceTransactionAllocationRequest;
use App\Modules\Pricing\Requests\StoreSupplierFuelInvoiceTransactionAllocationRequest;
use App\Modules\Pricing\Services\SupplierFuelInvoiceTransactionAllocationService;
use Illuminate\Http\JsonResponse;

final class SupplierFuelInvoiceTransactionAllocationController extends Controller
{
    public function store(
        StoreSupplierFuelInvoiceTransactionAllocationRequest $request,
        string $supplierFuelInvoice,
        SupplierFuelInvoiceTransactionAllocationService $service,
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
        ReverseSupplierFuelInvoiceTransactionAllocationRequest $request,
        string $supplierFuelInvoice,
        string $allocation,
        SupplierFuelInvoiceTransactionAllocationService $service,
    ): JsonResponse {
        $result = $service->reverse(
            $supplierFuelInvoice,
            $allocation,
            $request->validated(),
            (int) $request->attributes->get('organization_id'),
            $request->user(),
        );

        return response()->json(['data' => $result['data']]);
    }
}
