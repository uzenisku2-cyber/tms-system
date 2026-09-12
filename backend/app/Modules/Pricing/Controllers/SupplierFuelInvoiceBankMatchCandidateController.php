<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pricing\Requests\MaterializeSupplierFuelInvoiceBankMatchCandidateRequest;
use App\Modules\Pricing\Requests\ProposeSupplierFuelInvoiceBankMatchCandidateRequest;
use App\Modules\Pricing\Requests\ReviewSupplierFuelInvoiceBankMatchCandidateRequest;
use App\Modules\Pricing\Services\SupplierFuelInvoiceBankMatchCandidateService;
use App\Modules\Pricing\Services\SupplierFuelInvoiceBankPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SupplierFuelInvoiceBankMatchCandidateController extends Controller
{
    public function index(
        Request $request,
        string $supplierFuelInvoice,
        SupplierFuelInvoiceBankMatchCandidateService $service,
    ): JsonResponse {
        return response()->json(['data' => $service->index(
            $supplierFuelInvoice,
            (int) $request->attributes->get('organization_id'),
            $request->user(),
        )]);
    }

    public function store(
        ProposeSupplierFuelInvoiceBankMatchCandidateRequest $request,
        string $supplierFuelInvoice,
        SupplierFuelInvoiceBankMatchCandidateService $service,
    ): JsonResponse {
        $result = $service->propose(
            $supplierFuelInvoice,
            $request->validated(),
            (int) $request->attributes->get('organization_id'),
            $request->user(),
        );

        return response()->json(['data' => $result['data']], $result['replayed'] ? 200 : 201);
    }

    public function review(
        ReviewSupplierFuelInvoiceBankMatchCandidateRequest $request,
        string $supplierFuelInvoice,
        string $candidate,
        SupplierFuelInvoiceBankMatchCandidateService $service,
    ): JsonResponse {
        $result = $service->review(
            $supplierFuelInvoice,
            $candidate,
            $request->validated(),
            (int) $request->attributes->get('organization_id'),
            $request->user(),
        );

        return response()->json(['data' => $result['data']]);
    }

    public function materialize(
        MaterializeSupplierFuelInvoiceBankMatchCandidateRequest $request,
        string $supplierFuelInvoice,
        string $candidate,
        SupplierFuelInvoiceBankMatchCandidateService $service,
        SupplierFuelInvoiceBankPaymentService $paymentService,
    ): JsonResponse {
        $result = $service->materialize(
            $supplierFuelInvoice,
            $candidate,
            $request->validated(),
            (int) $request->attributes->get('organization_id'),
            $request->user(),
            $paymentService,
        );

        return response()->json(['data' => $result['data']], $result['replayed'] ? 200 : 201);
    }
}
