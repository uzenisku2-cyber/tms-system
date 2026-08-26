<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Models\User;
use App\Modules\Pricing\Requests\BillingOverviewRequest;
use App\Modules\Pricing\Services\BillingOverviewService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;

final class BillingOverviewController
{
    /** @throws AuthenticationException */
    public function index(
        BillingOverviewRequest $request,
        BillingOverviewService $service,
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return response()->json([
            'data' => $service->overview(
                $actor,
                $request->validated(),
            ),
        ]);
    }
}
