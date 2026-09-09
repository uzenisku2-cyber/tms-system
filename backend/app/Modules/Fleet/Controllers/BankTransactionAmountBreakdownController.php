<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Fleet\Requests\StoreBankTransactionAmountBreakdownRequest;
use App\Modules\Fleet\Services\BankTransactionAmountBreakdownService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BankTransactionAmountBreakdownController extends Controller
{
    public function __construct(private readonly BankTransactionAmountBreakdownService $service) {}

    public function show(Request $request, BankTransactionEvidence $evidence, OrganizationContext $context): JsonResponse
    {
        return response()->json(['data' => $this->service->show($evidence, $context->requireId(), $this->actor($request))]);
    }

    public function store(StoreBankTransactionAmountBreakdownRequest $request, BankTransactionEvidence $evidence, OrganizationContext $context): JsonResponse
    {
        return response()->json(['data' => $this->service->store($evidence, $request->validated(), $context->requireId(), $this->actor($request))], 201);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        return $actor;
    }
}
