<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Models\User;
use App\Modules\Pricing\Requests\CustomerReceivablesOverviewRequest;
use App\Modules\Pricing\Services\CustomerReceivablesOverviewService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;

final class CustomerReceivablesOverviewController
{
    /** @throws AuthenticationException */
    public function index(CustomerReceivablesOverviewRequest $request, CustomerReceivablesOverviewService $service): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return response()->json(['data' => $service->overview($actor, $request->validated())]);
    }
}
