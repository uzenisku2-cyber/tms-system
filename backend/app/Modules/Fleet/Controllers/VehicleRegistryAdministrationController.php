<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Fleet\Requests\IndexVehicleRegistryAdministrationRequest;
use App\Modules\Fleet\Requests\ReviewVehicleDocumentEvidenceRequest;
use App\Modules\Fleet\Requests\ReviewVehicleOwnershipRequest;
use App\Modules\Fleet\Requests\ReviewVehicleResponsibilityRequest;
use App\Modules\Fleet\Requests\ReviseVehicleComplianceEvidenceRequest;
use App\Modules\Fleet\Requests\ReviseVehicleFinancingEvidenceRequest;
use App\Modules\Fleet\Requests\ReviseVehicleIncidentEvidenceRequest;
use App\Modules\Fleet\Requests\ReviseVehicleInstallmentScheduleRequest;
use App\Modules\Fleet\Requests\ReviseVehicleInsuranceEvidenceRequest;
use App\Modules\Fleet\Requests\ReviseVehicleServiceEvidenceRequest;
use App\Modules\Fleet\Requests\StoreProgressiveVehicleRecordRequest;
use App\Modules\Fleet\Requests\StoreVehicleComplianceEvidenceRequest;
use App\Modules\Fleet\Requests\StoreVehicleDocumentEvidenceRequest;
use App\Modules\Fleet\Requests\StoreVehicleFinancingEvidenceRequest;
use App\Modules\Fleet\Requests\StoreVehicleIncidentEvidenceRequest;
use App\Modules\Fleet\Requests\StoreVehicleInstallmentScheduleRequest;
use App\Modules\Fleet\Requests\StoreVehicleInsuranceEvidenceRequest;
use App\Modules\Fleet\Requests\StoreVehicleOwnershipRequest;
use App\Modules\Fleet\Requests\StoreVehicleResponsibilityRequest;
use App\Modules\Fleet\Requests\StoreVehicleServiceEvidenceRequest;
use App\Modules\Fleet\Requests\TransitionVehicleLifecycleRequest;
use App\Modules\Fleet\Requests\UpdateProgressiveVehicleRecordRequest;
use App\Modules\Fleet\Requests\UpdateVehicleRecordFieldStatusRequest;
use App\Modules\Fleet\Services\ProgressiveVehicleRecordService;
use App\Modules\Fleet\Services\VehicleComplianceEvidenceService;
use App\Modules\Fleet\Services\VehicleDocumentEvidenceService;
use App\Modules\Fleet\Services\VehicleFinancingEvidenceService;
use App\Modules\Fleet\Services\VehicleIncidentEvidenceService;
use App\Modules\Fleet\Services\VehicleInstallmentScheduleAdministrationService;
use App\Modules\Fleet\Services\VehicleInsuranceEvidenceService;
use App\Modules\Fleet\Services\VehicleLifecycleTransitionService;
use App\Modules\Fleet\Services\VehicleOwnershipEvidenceService;
use App\Modules\Fleet\Services\VehicleRegistryAdministrationReadService;
use App\Modules\Fleet\Services\VehicleResponsibilityEvidenceService;
use App\Modules\Fleet\Services\VehicleServiceEvidenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class VehicleRegistryAdministrationController extends Controller
{
    public function __construct(
        private readonly VehicleRegistryAdministrationReadService $service,
        private readonly ProgressiveVehicleRecordService $writeService,
        private readonly VehicleDocumentEvidenceService $documentService,
        private readonly VehicleComplianceEvidenceService $complianceService,
        private readonly VehicleInsuranceEvidenceService $insuranceService,
        private readonly VehicleIncidentEvidenceService $incidentService,
        private readonly VehicleFinancingEvidenceService $financingService,
        private readonly VehicleInstallmentScheduleAdministrationService $installmentScheduleService,
        private readonly VehicleServiceEvidenceService $serviceEvidenceService,
        private readonly VehicleOwnershipEvidenceService $ownershipService,
        private readonly VehicleResponsibilityEvidenceService $responsibilityService,
        private readonly VehicleLifecycleTransitionService $lifecycleService,
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

    public function transitionLifecycle(TransitionVehicleLifecycleRequest $request, OrganizationContext $context, string $vehicle): JsonResponse
    {
        return response()->json($this->lifecycleService->transition($vehicle, $request->validated(), $context->requireId(), $this->actor($request)));
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

    public function storeCompliance(StoreVehicleComplianceEvidenceRequest $request, OrganizationContext $context, string $vehicle): JsonResponse
    {
        return response()->json($this->complianceService->store($vehicle, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function reviseCompliance(ReviseVehicleComplianceEvidenceRequest $request, OrganizationContext $context, string $vehicle, string $record): JsonResponse
    {
        return response()->json($this->complianceService->revise($vehicle, $record, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function storeInsurance(StoreVehicleInsuranceEvidenceRequest $request, OrganizationContext $context, string $vehicle): JsonResponse
    {
        return response()->json($this->insuranceService->store($vehicle, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function reviseInsurance(ReviseVehicleInsuranceEvidenceRequest $request, OrganizationContext $context, string $vehicle, string $record): JsonResponse
    {
        return response()->json($this->insuranceService->revise($vehicle, $record, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function storeService(StoreVehicleServiceEvidenceRequest $request, OrganizationContext $context, string $vehicle): JsonResponse
    {
        return response()->json($this->serviceEvidenceService->store($vehicle, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function reviseService(ReviseVehicleServiceEvidenceRequest $request, OrganizationContext $context, string $vehicle, string $record): JsonResponse
    {
        return response()->json($this->serviceEvidenceService->revise($vehicle, $record, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function storeIncident(StoreVehicleIncidentEvidenceRequest $request, OrganizationContext $context, string $vehicle): JsonResponse
    {
        return response()->json($this->incidentService->store($vehicle, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function reviseIncident(ReviseVehicleIncidentEvidenceRequest $request, OrganizationContext $context, string $vehicle, string $record): JsonResponse
    {
        return response()->json($this->incidentService->revise($vehicle, $record, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function storeFinancing(StoreVehicleFinancingEvidenceRequest $request, OrganizationContext $context, string $vehicle): JsonResponse
    {
        return response()->json($this->financingService->store($vehicle, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function reviseFinancing(ReviseVehicleFinancingEvidenceRequest $request, OrganizationContext $context, string $vehicle, string $record): JsonResponse
    {
        return response()->json($this->financingService->revise($vehicle, $record, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function storeInstallmentSchedule(StoreVehicleInstallmentScheduleRequest $request, OrganizationContext $context, string $vehicle): JsonResponse
    {
        return response()->json($this->installmentScheduleService->store($vehicle, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function reviseInstallmentSchedule(ReviseVehicleInstallmentScheduleRequest $request, OrganizationContext $context, string $vehicle, string $record): JsonResponse
    {
        return response()->json($this->installmentScheduleService->revise($vehicle, $record, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function storeOwnership(StoreVehicleOwnershipRequest $request, OrganizationContext $context, string $vehicle): JsonResponse
    {
        return response()->json($this->ownershipService->store($vehicle, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function reviewOwnership(ReviewVehicleOwnershipRequest $request, OrganizationContext $context, string $vehicle, string $ownership): JsonResponse
    {
        return response()->json($this->ownershipService->review($vehicle, $ownership, $request->validated(), $context->requireId(), $this->actor($request)));
    }

    public function storeResponsibility(StoreVehicleResponsibilityRequest $request, OrganizationContext $context, string $vehicle): JsonResponse
    {
        return response()->json($this->responsibilityService->store($vehicle, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function reviewResponsibility(ReviewVehicleResponsibilityRequest $request, OrganizationContext $context, string $vehicle, string $responsibility): JsonResponse
    {
        return response()->json($this->responsibilityService->review($vehicle, $responsibility, $request->validated(), $context->requireId(), $this->actor($request)));
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
