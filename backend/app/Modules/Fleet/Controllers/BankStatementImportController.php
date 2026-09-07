<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Fleet\Models\BankStatementImportBatch;
use App\Modules\Fleet\Requests\StoreBankStatementImportRequest;
use App\Modules\Fleet\Services\BankStatementImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use LogicException;

final class BankStatementImportController extends Controller
{
    public function index(Request $request, OrganizationContext $context, BankStatementImportService $service): JsonResponse
    {
        return response()->json(['data' => ['items' => $service->visibleBatches($context->requireId(), $this->actor($request))]]);
    }

    public function show(Request $request, BankStatementImportBatch $batch, OrganizationContext $context, BankStatementImportService $service): JsonResponse
    {
        return response()->json(['data' => $service->visibleBatch($batch, $context->requireId(), $this->actor($request))]);
    }

    public function store(StoreBankStatementImportRequest $request, OrganizationContext $context, BankStatementImportService $service): JsonResponse
    {
        $file = $request->file('file');
        if (! $file instanceof UploadedFile || ! is_string($file->getRealPath())) {
            throw new LogicException('The validated bank statement file is unavailable.');
        }

        return response()->json(['data' => $service->import($request->validated(), $context->requireId(), $this->actor($request), $file->getClientOriginalName(), $file->getRealPath())], 201);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        if (! $actor instanceof User || ! $actor->exists) {
            throw new LogicException('The bank statement import actor must be persisted.');
        }

        return $actor;
    }
}
