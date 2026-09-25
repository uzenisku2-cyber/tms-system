<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Fleet\Requests\IndexVehicleRegistryAdministrationRequest;
use App\Modules\Fleet\Requests\StoreProgressiveVehicleRecordRequest;
use App\Modules\Fleet\Requests\UpdateProgressiveVehicleRecordRequest;
use App\Modules\Fleet\Requests\UpdateVehicleRecordFieldStatusRequest;
use App\Modules\Fleet\Services\ProgressiveVehicleRecordService;
use App\Modules\Fleet\Services\VehicleRegistryAdministrationReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class VehicleRegistryAdministrationController extends Controller
{
    public function __construct(
        private readonly VehicleRegistryAdministrationReadService $service,
        private readonly ProgressiveVehicleRecordService $writeService,
    ) {}

    public function index(IndexVehicleRegistryAdministrationRequest $request, OrganizationContext $context): JsonResponse
    {
        return response()->json($this->service->index($request->validated(), $context->requireId(), $this->actor($request)));
    }

    public function store(StoreProgressiveVehicleRecordRequest $request, OrganizationContext $context): JsonResponse
    {
        return response()->json($this->writeService->create($request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function update(UpdateProgressiveVehicleRecordRequest $request, OrganizationContext $context, string $vehicle): JsonResponse
    {
        return response()->json($this->writeService->update($vehicle, $request->validated(), $context->requireId(), $this->actor($request)));
    }

    public function updateFieldStatus(UpdateVehicleRecordFieldStatusRequest $request, OrganizationContext $context, string $vehicle, string $fieldKey): JsonResponse
    {
        return response()->json($this->writeService->updateFieldStatus($vehicle, $fieldKey, $request->validated(), $context->requireId(), $this->actor($request)));
    }

    public function show(Request $request, OrganizationContext $context, string $vehicle): JsonResponse
    {
        return response()->json($this->service->show($vehicle, $context->requireId(), $this->actor($request)));
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            abort(401);
        }

        return $actor;
    }
}
