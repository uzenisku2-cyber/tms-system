<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Fuel\Models\FuelSurcharge;
use App\Modules\Fuel\Requests\StoreFuelSurchargeRequest;
use App\Modules\Fuel\Services\FuelSurchargeManagementService;
use App\Modules\Fuel\Services\FuelSurchargeRecipientVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

final class FuelSurchargeController
{
    public function index(
        OrganizationContext $context,
        FuelSurchargeManagementService $service,
    ): JsonResponse {
        $items = $service->internalIndex($context->requireId())
            ->map(fn (FuelSurcharge $item): array =>
                $service->internalPayload($item));

        return response()->json(['data' => ['items' => $items]]);
    }

    public function show(
        FuelSurcharge $fuelSurcharge,
        OrganizationContext $context,
        FuelSurchargeManagementService $service,
    ): JsonResponse {
        if (
            (int) $fuelSurcharge->owner_organization_id
            !== $context->requireId()
        ) {
            abort(404);
        }

        return response()->json([
            'data' => $service->internalPayload(
                $fuelSurcharge->load('recipientRates'),
            ),
        ]);
    }

    public function store(
        StoreFuelSurchargeRequest $request,
        OrganizationContext $context,
        FuelSurchargeManagementService $service,
    ): JsonResponse {
        $surcharge = $service->create(
            $context->requireId(),
            $this->actor($request),
            $request->validated(),
        );

        return response()->json([
            'data' => $service->internalPayload($surcharge),
        ], 201);
    }

    public function mine(
        Request $request,
        OrganizationContext $context,
        FuelSurchargeRecipientVisibilityService $visibility,
    ): JsonResponse {
        $actor = $this->actor($request);
        $items = $visibility
            ->ownRates($actor, $context->requireId())
            ->map(fn ($rate): array => $visibility->recipientPayload($rate));

        return response()->json(['data' => ['items' => $items]]);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        if (! $actor instanceof User || ! $actor->exists) {
            throw new LogicException('The fuel-surcharge actor must be persisted.');
        }

        return $actor;
    }
}
