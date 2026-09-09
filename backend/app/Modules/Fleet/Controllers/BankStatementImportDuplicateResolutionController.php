<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Fleet\Models\BankStatementImportDuplicateCandidate;
use App\Modules\Fleet\Requests\ResolveBankStatementImportDuplicateCandidateRequest;
use App\Modules\Fleet\Services\BankStatementImportDuplicateResolutionService;
use Illuminate\Http\JsonResponse;
use LogicException;

final class BankStatementImportDuplicateResolutionController extends Controller
{
    public function store(ResolveBankStatementImportDuplicateCandidateRequest $request, BankStatementImportDuplicateCandidate $candidate, OrganizationContext $context, BankStatementImportDuplicateResolutionService $service): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User || ! $actor->exists) {
            throw new LogicException('The duplicate resolution actor must be persisted.');
        }

        return response()->json(['data' => $service->resolve($candidate, $request->validated(), $context->requireId(), $actor)], 201);
    }
}
