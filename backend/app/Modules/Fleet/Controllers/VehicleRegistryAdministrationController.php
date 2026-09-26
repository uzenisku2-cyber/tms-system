<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Fleet\Requests\IndexVehicleRegistryAdministrationRequest;
use App\Modules\Fleet\Requests\ReviewVehicleDocumentEvidenceRequest;
use App\Modules\Fleet\Requests\ReviewVehicleOwnershipRequest;
use App\Modules\Fleet\Requests\StoreProgressiveVehicleRecordRequest;
use App\Modules\Fleet\Requests\StoreVehicleDocumentEvidenceRequest;
use App\Modules\Fleet\Requests\StoreVehicleOwnershipRequest;
use App\Modules\Fleet\Requests\UpdateProgressiveVehicleRecordRequest;
use App\Modules\Fleet\Requests\UpdateVehicleRecordFieldStatusRequest;
use App\Modules\Fleet\Services\ProgressiveVehicleRecordService;
use App\Modules\Fleet\Services\VehicleDocumentEvidenceService;
use App\Modules\Fleet\Services\VehicleOwnershipEvidenceService;
use App\Modules\Fleet\Services\VehicleRegistryAdministrationReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class VehicleRegistryAdministrationController extends Controller
{
    public function __construct(
        private readonly VehicleRegistryAdministrationReadService $service,
        private readonly ProgressiveVehicleRecordService $writeService,
        private readonly VehicleDocumentEvidenceService $documentService,
        private readonly VehicleOwnershipEvidenceService $ownershipService,
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

    public function storeDocument(StoreVehicleDocumentEvidenceRequest $request, OrganizationContext $context, string $vehicle): JsonResponse
    {
        return response()->json($this->documentService->store($vehicle, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function reviewDocument(ReviewVehicleDocumentEvidenceRequest $request, OrganizationContext $context, string $vehicle, string $document): JsonResponse
    {
        return response()->json($this->documentService->review($vehicle, $document, $request->validated(), $context->requireId(), $this->actor($request)));
    }

    public function storeOwnership(StoreVehicleOwnershipRequest $request, OrganizationContext $context, string $vehicle): JsonResponse
    {
        return response()->json($this->ownershipService->store($vehicle, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function reviewOwnership(ReviewVehicleOwnershipRequest $request, OrganizationContext $context, string $vehicle, string $ownership): JsonResponse
    {
        return response()->json($this->ownershipService->review($vehicle, $ownership, $request->validated(), $context->requireId(), $this->actor($request)));
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
