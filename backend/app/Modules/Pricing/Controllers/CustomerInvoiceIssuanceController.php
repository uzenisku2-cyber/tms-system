<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Models\User;
use App\Modules\Pricing\Requests\IssueCustomerInvoiceRequest;
use App\Modules\Pricing\Services\CustomerInvoiceIssuanceService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;

final class CustomerInvoiceIssuanceController
{
    /** @throws AuthenticationException */
    public function issue(
        IssueCustomerInvoiceRequest $request,
        string $customerInvoice,
        CustomerInvoiceIssuanceService $service,
    ): JsonResponse {
        $actor = $request->user();
        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return response()->json(['data' => $service->issue($actor, $customerInvoice, $request->validated())]);
    }
}
