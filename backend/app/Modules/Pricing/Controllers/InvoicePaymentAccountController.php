<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Models\User;
use App\Modules\Pricing\Requests\StoreInvoicePaymentAccountRequest;
use App\Modules\Pricing\Services\InvoicePaymentAccountService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InvoicePaymentAccountController
{
    public function show(Request $request, InvoicePaymentAccountService $service): JsonResponse
    {
        return response()->json(['data' => $service->current()]);
    }

    /** @throws AuthenticationException */
    public function store(StoreInvoicePaymentAccountRequest $request, InvoicePaymentAccountService $service): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return response()->json(['data' => $service->store($actor, $request->validated())], 201);
    }
}
