<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Fuel\Models\FuelImportBatch;
use App\Modules\Fuel\Requests\StoreFuelImportRequest;
use App\Modules\Fuel\Services\FuelTransactionImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use LogicException;

final class FuelTransactionImportController
{
    public function index(OrganizationContext $context, FuelTransactionImportService $service): JsonResponse
    {
        return response()->json(['data' => ['items' => $service->visibleBatches($context->requireId())]]);
    }

    public function show(FuelImportBatch $batch, OrganizationContext $context, FuelTransactionImportService $service): JsonResponse
    {
        return response()->json(['data' => $service->visibleBatch($batch, $context->requireId())]);
    }

    public function store(StoreFuelImportRequest $request, OrganizationContext $context, FuelTransactionImportService $service): JsonResponse
    {
        $file = $request->file('file');
        if (! $file instanceof UploadedFile) {
            throw new LogicException('The validated fuel import file is unavailable.');
        }

        $path = $file->getRealPath();
        if (! is_string($path)) {
            throw new LogicException('The validated fuel import path is unavailable.');
        }

        return response()->json(['data' => $service->import($context->requireId(), $this->actor($request), (string) $request->validated('provider'), $file->getClientOriginalName(), $path)], 201);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        if (! $actor instanceof User || ! $actor->exists) {
            throw new LogicException('The fuel-import actor must be persisted.');
        }

        return $actor;
    }
}
