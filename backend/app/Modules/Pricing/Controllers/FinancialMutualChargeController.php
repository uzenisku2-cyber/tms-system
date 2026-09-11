<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Pricing\Requests\StoreFinancialMutualChargeRequest;
use App\Modules\Pricing\Requests\TransitionFinancialMutualChargeRequest;
use App\Modules\Pricing\Services\FinancialMutualChargeService;
use Illuminate\Http\JsonResponse;

final class FinancialMutualChargeController extends Controller
{
    public function store(StoreFinancialMutualChargeRequest $request, FinancialMutualChargeService $service): JsonResponse
    {
        $result = $service->store($request->validated(), $this->organizationId($request), $this->actor($request));

        return response()->json(['data' => $result['data']], $result['replayed'] ? 200 : 201);
    }

    public function confirm(string $financialMutualCharge, TransitionFinancialMutualChargeRequest $request, FinancialMutualChargeService $service): JsonResponse
    {
        return $this->transition($service->confirm($financialMutualCharge, $request->validated(), $this->organizationId($request), $this->actor($request)));
    }

    public function dispute(string $financialMutualCharge, TransitionFinancialMutualChargeRequest $request, FinancialMutualChargeService $service): JsonResponse
    {
        return $this->transition($service->dispute($financialMutualCharge, $request->validated(), $this->organizationId($request), $this->actor($request)));
    }

    public function reverse(string $financialMutualCharge, TransitionFinancialMutualChargeRequest $request, FinancialMutualChargeService $service): JsonResponse
    {
        return $this->transition($service->reverse($financialMutualCharge, $request->validated(), $this->organizationId($request), $this->actor($request)));
    }

    private function transition(array $result): JsonResponse
    {
        return response()->json(['data' => $result['data']], $result['replayed'] ? 200 : 201);
    }

    private function organizationId(StoreFinancialMutualChargeRequest|TransitionFinancialMutualChargeRequest $request): int
    {
        return (int) $request->attributes->get('organization_id');
    }

    private function actor(StoreFinancialMutualChargeRequest|TransitionFinancialMutualChargeRequest $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        return $actor;
    }
}
