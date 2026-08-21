<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Controllers;

use App\Core\Http\BaseController;
use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\DailyReports\Models\DriverQualityProfileBinding;
use App\Modules\DailyReports\Requests\ActivateDriverQualityProfileVersionRequest;
use App\Modules\DailyReports\Requests\BindDriverQualityProfileRequest;
use App\Modules\DailyReports\Requests\DriverQualityProfileEffectiveRequest;
use App\Modules\DailyReports\Requests\EndDriverQualityProfileBindingRequest;
use App\Modules\DailyReports\Requests\StoreDriverQualityProfileRequest;
use App\Modules\DailyReports\Requests\StoreDriverQualityProfileVersionRequest;
use App\Modules\DailyReports\Requests\UpdateDriverQualityProfileVersionRequest;
use App\Modules\DailyReports\Resources\DriverQualityProfileBindingResource;
use App\Modules\DailyReports\Resources\DriverQualityProfileResource;
use App\Modules\DailyReports\Resources\DriverQualityProfileVersionResource;
use App\Modules\DailyReports\Services\DriverQualityProfileQueryService;
use App\Modules\DailyReports\Services\DriverQualityProfileWriteService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DriverQualityProfileController extends BaseController
{
    public function index(
        OrganizationContext $context,
        DriverQualityProfileQueryService $queries,
    ): JsonResponse {
        return $this->success([
            'items' => DriverQualityProfileResource::collection(
                $queries->profiles($context->requireId()),
            ),
        ]);
    }

    public function show(
        OrganizationContext $context,
        string $qualityProfile,
        DriverQualityProfileQueryService $queries,
    ): JsonResponse {
        return $this->success(
            new DriverQualityProfileResource(
                $queries->profile(
                    $context->requireId(),
                    $qualityProfile,
                ),
            ),
        );
    }

    public function bindings(
        Request $request,
        OrganizationContext $context,
        DriverQualityProfileQueryService $queries,
    ): JsonResponse {
        return $this->success([
            'items' => DriverQualityProfileBindingResource::collection(
                $queries->bindings(
                    $this->actor($request),
                    $context->requireId(),
                ),
            ),
        ]);
    }

    public function bindingTargets(
        Request $request,
        OrganizationContext $context,
        DriverQualityProfileQueryService $queries,
    ): JsonResponse {
        return $this->success(
            $queries->bindingTargets(
                $this->actor($request),
                $context->requireId(),
            ),
        );
    }

    public function effective(
        DriverQualityProfileEffectiveRequest $request,
        OrganizationContext $context,
        DriverQualityProfileQueryService $queries,
    ): JsonResponse {
        $resolution = $queries->effective(
            actor: $this->actor($request),
            organizationId: $context->requireId(),
            data: $request->validated(),
        );

        return $this->success([
            'reason' => $resolution->reason,
            'scope_type' => $resolution->scopeType,
            'binding' => $resolution->binding === null
                ? null
                : new DriverQualityProfileBindingResource(
                    $resolution->binding,
                ),
            'profile' => $resolution->profile === null
                ? null
                : new DriverQualityProfileResource(
                    $resolution->profile,
                ),
            'version' => $resolution->version === null
                ? null
                : new DriverQualityProfileVersionResource(
                    $resolution->version,
                ),
        ]);
    }

    public function store(
        StoreDriverQualityProfileRequest $request,
        OrganizationContext $context,
        DriverQualityProfileWriteService $writes,
    ): JsonResponse {
        return $this->success(
            new DriverQualityProfileResource(
                $writes->createDraft(
                    actor: $this->actor($request),
                    organizationId: $context->requireId(),
                    data: $request->validated(),
                ),
            ),
            'Driver quality profile draft created.',
            201,
        );
    }

    public function storeVersion(
        StoreDriverQualityProfileVersionRequest $request,
        OrganizationContext $context,
        string $qualityProfile,
        DriverQualityProfileWriteService $writes,
    ): JsonResponse {
        return $this->success(
            new DriverQualityProfileVersionResource(
                $writes->createDraftVersion(
                    actor: $this->actor($request),
                    organizationId: $context->requireId(),
                    publicId: $qualityProfile,
                    data: $request->validated(),
                ),
            ),
            'Driver quality profile draft version created.',
            201,
        );
    }

    public function updateVersion(
        UpdateDriverQualityProfileVersionRequest $request,
        OrganizationContext $context,
        string $qualityProfile,
        string $version,
        DriverQualityProfileWriteService $writes,
    ): JsonResponse {
        return $this->success(
            new DriverQualityProfileVersionResource(
                $writes->updateDraftVersion(
                    organizationId: $context->requireId(),
                    publicId: $qualityProfile,
                    versionNumber: (int) $version,
                    data: $request->validated(),
                ),
            ),
            'Driver quality profile draft version updated.',
        );
    }

    public function activateVersion(
        ActivateDriverQualityProfileVersionRequest $request,
        OrganizationContext $context,
        string $qualityProfile,
        string $version,
        DriverQualityProfileWriteService $writes,
    ): JsonResponse {
        return $this->success(
            new DriverQualityProfileVersionResource(
                $writes->activateDraftVersion(
                    actor: $this->actor($request),
                    organizationId: $context->requireId(),
                    publicId: $qualityProfile,
                    versionNumber: (int) $version,
                    data: $request->validated(),
                ),
            ),
            'Driver quality profile version activated.',
        );
    }

    public function bindOrganization(
        BindDriverQualityProfileRequest $request,
        OrganizationContext $context,
        DriverQualityProfileWriteService $writes,
    ): JsonResponse {
        return $this->bindingResponse(
            $writes->replaceOrganizationBinding(
                actor: $this->actor($request),
                organizationId: $context->requireId(),
                profilePublicId: $request->string(
                    'profile_public_id',
                )->toString(),
                validFrom: $request->string('valid_from')->toString(),
            ),
        );
    }

    public function bindCarrier(
        BindDriverQualityProfileRequest $request,
        OrganizationContext $context,
        string $relationship,
        DriverQualityProfileWriteService $writes,
    ): JsonResponse {
        return $this->bindingResponse(
            $writes->replaceCarrierBinding(
                actor: $this->actor($request),
                organizationId: $context->requireId(),
                relationshipId: (int) $relationship,
                profilePublicId: $request->string(
                    'profile_public_id',
                )->toString(),
                validFrom: $request->string('valid_from')->toString(),
            ),
        );
    }

    public function bindDriver(
        BindDriverQualityProfileRequest $request,
        OrganizationContext $context,
        string $assignment,
        DriverQualityProfileWriteService $writes,
    ): JsonResponse {
        return $this->bindingResponse(
            $writes->replaceDriverBinding(
                actor: $this->actor($request),
                organizationId: $context->requireId(),
                assignmentId: (int) $assignment,
                profilePublicId: $request->string(
                    'profile_public_id',
                )->toString(),
                validFrom: $request->string('valid_from')->toString(),
            ),
        );
    }

    public function endOrganizationBinding(
        EndDriverQualityProfileBindingRequest $request,
        OrganizationContext $context,
        DriverQualityProfileWriteService $writes,
    ): JsonResponse {
        return $this->success(
            $writes->endOrganizationBinding(
                organizationId: $context->requireId(),
                effectiveFrom: $request->string(
                    'effective_from',
                )->toString(),
            ),
            'Driver quality profile binding ended.',
        );
    }

    public function endCarrierBinding(
        EndDriverQualityProfileBindingRequest $request,
        OrganizationContext $context,
        string $relationship,
        DriverQualityProfileWriteService $writes,
    ): JsonResponse {
        return $this->success(
            $writes->endCarrierBinding(
                organizationId: $context->requireId(),
                relationshipId: (int) $relationship,
                effectiveFrom: $request->string(
                    'effective_from',
                )->toString(),
            ),
            'Driver quality profile binding ended.',
        );
    }

    public function endDriverBinding(
        EndDriverQualityProfileBindingRequest $request,
        OrganizationContext $context,
        string $assignment,
        DriverQualityProfileWriteService $writes,
    ): JsonResponse {
        return $this->success(
            $writes->endDriverBinding(
                actor: $this->actor($request),
                organizationId: $context->requireId(),
                assignmentId: (int) $assignment,
                effectiveFrom: $request->string(
                    'effective_from',
                )->toString(),
            ),
            'Driver quality profile binding ended.',
        );
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return $actor;
    }

    private function bindingResponse(
        DriverQualityProfileBinding $binding,
    ): JsonResponse {
        return $this->success(
            new DriverQualityProfileBindingResource($binding),
            'Driver quality profile binding saved.',
        );
    }
}
