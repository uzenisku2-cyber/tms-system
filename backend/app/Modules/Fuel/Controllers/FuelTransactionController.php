<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Modules\Fuel\Requests\IndexFuelTransactionRequest;
use App\Modules\Fuel\Services\FuelTransactionAdministrationService;
use Illuminate\Http\JsonResponse;

final class FuelTransactionController
{
    public function index(
        IndexFuelTransactionRequest $request,
        OrganizationContext $context,
        FuelTransactionAdministrationService $service,
    ): JsonResponse {
        $data = $service->index($context->requireId(), $request->validated());
        $data['capabilities'] = [
            'can_manage_reconciliation' => $request->user()?->can('compensation.manage') ?? false,
        ];

        return response()->json(['data' => $data]);
    }
}
