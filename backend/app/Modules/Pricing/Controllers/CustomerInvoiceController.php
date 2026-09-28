<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Models\User;
use App\Modules\Pricing\Requests\StoreCustomerInvoiceDraftRequest;
use App\Modules\Pricing\Services\CustomerInvoiceDraftService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerInvoiceController
{
    /** @throws AuthenticationException */
    public function store(StoreCustomerInvoiceDraftRequest $request, CustomerInvoiceDraftService $service): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return response()->json(['data' => $service->create($actor, $request->validated())], 201);
    }

    /** @throws AuthenticationException */
    public function show(Request $request, string $customerInvoice, CustomerInvoiceDraftService $service): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return response()->json(['data' => $service->show($customerInvoice)]);
    }
}
