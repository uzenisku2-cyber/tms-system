<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Modules\Fleet\Requests\IndexBankTransactionEvidenceRequest;
use App\Modules\Fleet\Requests\StoreBankTransactionEvidenceRequest;
use App\Modules\Fleet\Services\BankTransactionEvidenceAdministrationReadService;
use App\Modules\Fleet\Services\BankTransactionEvidenceService;
use Illuminate\Http\JsonResponse;

final class BankTransactionEvidenceController extends Controller
{
    public function __construct(private readonly BankTransactionEvidenceService $service) {}

    public function index(IndexBankTransactionEvidenceRequest $request, OrganizationContext $context, BankTransactionEvidenceAdministrationReadService $read): JsonResponse
    {
        $actor = $request->user();
        if ($actor === null) {
            abort(401);
        }

        return response()->json(['data' => $read->index($request->validated(), $context->requireId(), $actor)]);
    }

    public function show(string $evidence, IndexBankTransactionEvidenceRequest $request, OrganizationContext $context, BankTransactionEvidenceAdministrationReadService $read): JsonResponse
    {
        $actor = $request->user();
        if ($actor === null) {
            abort(401);
        }

        return response()->json(['data' => $read->show($evidence, $context->requireId(), $actor)]);
    }

    public function store(StoreBankTransactionEvidenceRequest $request, OrganizationContext $context): JsonResponse
    {
        $actor = $request->user();
        if ($actor === null) {
            abort(401);
        }

        return response()->json($this->service->record($request->validated(), $context->requireId(), $actor), 201);
    }
}
