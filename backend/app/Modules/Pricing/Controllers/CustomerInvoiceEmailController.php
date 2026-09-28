<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Models\User;
use App\Modules\Pricing\Requests\SendCustomerInvoiceEmailRequest;
use App\Modules\Pricing\Services\CustomerInvoiceEmailService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerInvoiceEmailController
{
    public function show(Request $request, string $customerInvoice, CustomerInvoiceEmailService $service): JsonResponse
    {
        return response()->json(['data' => $service->show($customerInvoice)]);
    }

    /** @throws AuthenticationException */
    public function store(SendCustomerInvoiceEmailRequest $request, string $customerInvoice, CustomerInvoiceEmailService $service): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return response()->json(['data' => $service->request($customerInvoice, $actor, $request->validated())], 202);
    }
}
