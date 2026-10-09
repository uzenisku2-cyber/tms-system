<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Fleet\Models\VehicleDocument;
use App\Modules\Fleet\Requests\IndexVehicleRegistryAdministrationRequest;
use App\Modules\Fleet\Requests\ReviewVehicleDocumentEvidenceRequest;
use App\Modules\Fleet\Requests\ReviewVehicleOwnershipRequest;
use App\Modules\Fleet\Requests\ReviewVehicleResponsibilityRequest;
use App\Modules\Fleet\Requests\ReviseVehicleComplianceEvidenceRequest;
use App\Modules\Fleet\Requests\ReviseVehicleFinancingEvidenceRequest;
use App\Modules\Fleet\Requests\ReviseVehicleIncidentEvidenceRequest;
use App\Modules\Fleet\Requests\ReviseVehicleInstallmentRequest;
use App\Modules\Fleet\Requests\ReviseVehicleInstallmentScheduleRequest;
use App\Modules\Fleet\Requests\ReviseVehicleInsuranceEvidenceRequest;
use App\Modules\Fleet\Requests\ReviseVehicleOwnershipRequest;
use App\Modules\Fleet\Requests\ReviseVehicleProvisionEvidenceRequest;
use App\Modules\Fleet\Requests\ReviseVehicleProvisionPriceRequest;
use App\Modules\Fleet\Requests\ReviseVehicleServiceEvidenceRequest;
use App\Modules\Fleet\Requests\StoreProgressiveVehicleRecordRequest;
use App\Modules\Fleet\Requests\StoreVehicleComplianceEvidenceRequest;
use App\Modules\Fleet\Requests\StoreVehicleDocumentEvidenceRequest;
use App\Modules\Fleet\Requests\StoreVehicleFinancingEvidenceRequest;
use App\Modules\Fleet\Requests\StoreVehicleIncidentEvidenceRequest;
use App\Modules\Fleet\Requests\StoreVehicleInstallmentRequest;
use App\Modules\Fleet\Requests\StoreVehicleInstallmentScheduleRequest;
use App\Modules\Fleet\Requests\StoreVehicleInsuranceEvidenceRequest;
use App\Modules\Fleet\Requests\StoreVehicleOwnershipRequest;
use App\Modules\Fleet\Requests\StoreVehicleProvisionEvidenceRequest;
use App\Modules\Fleet\Requests\StoreVehicleProvisionPriceRequest;
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
use App\Modules\Fleet\Services\VehicleInstallmentAdministrationService;
use App\Modules\Fleet\Services\VehicleInstallmentScheduleAdministrationService;
use App\Modules\Fleet\Services\VehicleInsuranceEvidenceService;
use App\Modules\Fleet\Services\VehicleLifecycleTransitionService;
use App\Modules\Fleet\Services\VehicleOwnershipEvidenceService;
use App\Modules\Fleet\Services\VehicleProvisionEvidenceService;
use App\Modules\Fleet\Services\VehicleProvisionPriceAdministrationService;
use App\Modules\Fleet\Services\VehicleRegistryAdministrationReadService;
use App\Modules\Fleet\Services\VehicleResponsibilityEvidenceService;
use App\Modules\Fleet\Services\VehicleServiceEvidenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

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
        private readonly VehicleProvisionEvidenceService $provisionService,
        private readonly VehicleProvisionPriceAdministrationService $provisionPriceService,
        private readonly VehicleInstallmentScheduleAdministrationService $installmentScheduleService,
        private readonly VehicleInstallmentAdministrationService $installmentService,
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
        $organizationId = $context->requireId();
        $actor = $this->actor($request);
        $this->service->show($vehicle, $organizationId, $actor);
        $data = $request->validated();
        unset($data['file']);
        $path = null;

        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $directory = 'vehicle-documents/'.$organizationId.'/'.$vehicle;
                $name = (string) Str::uuid().'.'.$file->extension();
                $stored = $file->storeAs($directory, $name, 'local');
                if (! is_string($stored) || $stored === '') {
                    abort(500, 'Document file could not be stored.');
                }
                $path = $stored;
                $data['storage_reference'] = 'managed-vehicle-document:'.$path;
            }

            $result = $this->documentService->store(
                $vehicle, $data, $organizationId, $actor,
            );
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return response()->json($result, 201);
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

    public function storeProvision(StoreVehicleProvisionEvidenceRequest $request, OrganizationContext $context, string $vehicle): JsonResponse
    {
        return response()->json($this->provisionService->store($vehicle, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function reviseProvision(ReviseVehicleProvisionEvidenceRequest $request, OrganizationContext $context, string $vehicle, string $record): JsonResponse
    {
        return response()->json($this->provisionService->revise($vehicle, $record, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function storeProvisionPrice(StoreVehicleProvisionPriceRequest $request, OrganizationContext $context, string $vehicle): JsonResponse
    {
        return response()->json($this->provisionPriceService->store($vehicle, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function reviseProvisionPrice(ReviseVehicleProvisionPriceRequest $request, OrganizationContext $context, string $vehicle, string $record): JsonResponse
    {
        return response()->json($this->provisionPriceService->revise($vehicle, $record, $request->validated(), $context->requireId(), $this->actor($request)), 201);
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

    public function storeInstallment(StoreVehicleInstallmentRequest $request, OrganizationContext $context, string $vehicle): JsonResponse
    {
        return response()->json($this->installmentService->store($vehicle, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function reviseInstallment(ReviseVehicleInstallmentRequest $request, OrganizationContext $context, string $vehicle, string $record): JsonResponse
    {
        return response()->json($this->installmentService->revise($vehicle, $record, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function storeOwnership(StoreVehicleOwnershipRequest $request, OrganizationContext $context, string $vehicle): JsonResponse
    {
        return response()->json($this->ownershipService->store($vehicle, $request->validated(), $context->requireId(), $this->actor($request)), 201);
    }

    public function reviseOwnership(ReviseVehicleOwnershipRequest $request, OrganizationContext $context, string $vehicle, string $ownership): JsonResponse
    {
        return response()->json($this->ownershipService->revise($vehicle, $ownership, $request->validated(), $context->requireId(), $this->actor($request)));
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

    public function downloadDocument(
        Request $request,
        OrganizationContext $context,
        string $vehicle,
        string $document,
    ): StreamedResponse {
        $organizationId = $context->requireId();
        $actor = $this->actor($request);
        $this->service->show($vehicle, $organizationId, $actor);

        $evidence = VehicleDocument::query()
            ->where('public_id', $document)
            ->where('organization_context_id', $organizationId)
            ->whereHas('vehicle', fn ($query) => $query->where('public_id', $vehicle))
            ->firstOrFail();

        abort_unless(
            $evidence->access_classification === 'operational'
                || $actor->can('vehicle.manage'),
            403,
        );

        $prefix = 'managed-vehicle-document:vehicle-documents/'
            .$organizationId.'/'.$vehicle.'/';
        $reference = (string) $evidence->storage_reference;
        abort_unless(str_starts_with($reference, $prefix), 404);

        $name = substr($reference, strlen($prefix));
        abort_unless(
            preg_match('/^[a-f0-9-]{36}\.(pdf|jpg|jpeg|png)$/i', $name) === 1,
            404,
        );

        $path = substr($reference, strlen('managed-vehicle-document:'));
        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        return $disk->download($path, $name, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
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
