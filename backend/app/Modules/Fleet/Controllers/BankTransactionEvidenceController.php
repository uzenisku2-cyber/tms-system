<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Modules\Fleet\Requests\StoreBankTransactionEvidenceRequest;
use App\Modules\Fleet\Services\BankTransactionEvidenceService;
use Illuminate\Http\JsonResponse;

final class BankTransactionEvidenceController extends Controller
{
    public function __construct(private readonly BankTransactionEvidenceService $service) {}

    public function store(StoreBankTransactionEvidenceRequest $request, OrganizationContext $context): JsonResponse
    {
        $actor = $request->user();
        if ($actor === null) {
            abort(401);
        }

        return response()->json($this->service->record($request->validated(), $context->requireId(), $actor), 201);
    }
}
