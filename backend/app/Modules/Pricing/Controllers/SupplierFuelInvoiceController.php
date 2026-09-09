<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Modules\Pricing\Requests\StoreSupplierFuelInvoiceRequest;
use App\Modules\Pricing\Services\SupplierFuelInvoiceService;
use Illuminate\Http\JsonResponse;

final class SupplierFuelInvoiceController extends Controller
{
    public function __construct(private readonly SupplierFuelInvoiceService $service) {}

    public function store(StoreSupplierFuelInvoiceRequest $request, OrganizationContext $context): JsonResponse
    {
        $result = $this->service->store($request->validated(), $context->requireId(), $request->user());

        return response()->json(['data' => $result['data']], $result['replayed'] ? 200 : 201);
    }
}
