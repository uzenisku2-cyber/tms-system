<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Models\User;
use App\Modules\Pricing\Requests\AllocateCustomerInvoiceBankPaymentRequest;
use App\Modules\Pricing\Requests\ReverseCustomerInvoiceBankPaymentRequest;
use App\Modules\Pricing\Services\CustomerInvoiceBankPaymentService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerInvoiceBankPaymentController
{
    public function index(Request $request, string $customerInvoice, CustomerInvoiceBankPaymentService $service): JsonResponse
    {
        return response()->json(['data' => $service->index($customerInvoice)]);
    }

    /** @throws AuthenticationException */
    public function store(AllocateCustomerInvoiceBankPaymentRequest $request, string $customerInvoice, CustomerInvoiceBankPaymentService $service): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return response()->json(['data' => $service->allocate($customerInvoice, $request->validated(), $actor)], 201);
    }

    /** @throws AuthenticationException */
    public function reverse(ReverseCustomerInvoiceBankPaymentRequest $request, string $customerInvoice, string $payment, CustomerInvoiceBankPaymentService $service): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return response()->json(['data' => $service->reverse($customerInvoice, $payment, $request->validated(), $actor)]);
    }
}
