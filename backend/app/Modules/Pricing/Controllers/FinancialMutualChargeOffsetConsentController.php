<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Pricing\Requests\DecideFinancialMutualChargeOffsetRequest;
use App\Modules\Pricing\Services\FinancialMutualChargeOffsetConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FinancialMutualChargeOffsetConsentController extends Controller
{
    public function index(Request $request, FinancialMutualChargeOffsetConsentService $service): JsonResponse
    {
        return response()->json(['data' => $service->index((int) $request->attributes->get('organization_id'), $this->actor($request))]);
    }

    public function store(string $financialMutualCharge, DecideFinancialMutualChargeOffsetRequest $request, FinancialMutualChargeOffsetConsentService $service): JsonResponse
    {
        $result = $service->decide($financialMutualCharge, $request->validated(), (int) $request->attributes->get('organization_id'), $this->actor($request));

        return response()->json(['data' => $result['data']], $result['replayed'] ? 200 : 201);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        return $actor;
    }
}
