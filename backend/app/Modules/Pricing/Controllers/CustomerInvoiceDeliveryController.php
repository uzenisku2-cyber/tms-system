<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Models\User;
use App\Modules\Pricing\Requests\StoreCustomerInvoiceDeliveryEventRequest;
use App\Modules\Pricing\Services\CustomerInvoiceDeliveryService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerInvoiceDeliveryController
{
    public function index(Request $request, string $customerInvoice, CustomerInvoiceDeliveryService $service): JsonResponse
    {
        return response()->json(['data' => $service->history($customerInvoice)]);
    }

    /** @throws AuthenticationException */
    public function store(StoreCustomerInvoiceDeliveryEventRequest $request, string $customerInvoice, CustomerInvoiceDeliveryService $service): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return response()->json(['data' => $service->record($customerInvoice, $actor, $request->validated())], 201);
    }
}
