<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Core\Http\BaseController;
use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Pricing\Requests\ActivateDriverPriceListVersionRequest;
use App\Modules\Pricing\Requests\ApproveDriverPriceListVersionRequest;
use App\Modules\Pricing\Requests\DriverPriceListIndexRequest;
use App\Modules\Pricing\Requests\ExpireDriverPriceListVersionRequest;
use App\Modules\Pricing\Requests\StoreDriverPriceListRequest;
use App\Modules\Pricing\Requests\StoreDriverPriceListVersionRequest;
use App\Modules\Pricing\Requests\UpdateDriverPriceListVersionRequest;
use App\Modules\Pricing\Resources\DriverPriceListResource;
use App\Modules\Pricing\Resources\DriverPriceListVersionResource;
use App\Modules\Pricing\Services\DriverPriceListQueryService;
use App\Modules\Pricing\Services\DriverPriceListWriteService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DriverPriceListController extends BaseController
{
    public function __construct(
        private readonly DriverPriceListWriteService $writes,
    ) {}

    public function store(
        StoreDriverPriceListRequest $request,
        OrganizationContext $context,
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return $this->success(
            new DriverPriceListResource(
                $this->writes->createDraft(
                    actor: $actor,
                    organizationId: $context->requireId(),
                    data: $request->validated(),
                ),
            ),
            'Driver price list draft created.',
            201,
        );
    }

    public function storeVersion(
        StoreDriverPriceListVersionRequest $request,
        OrganizationContext $context,
        string $driverPriceList,
    ): JsonResponse {
        return $this->success(
            new DriverPriceListVersionResource(
                $this->writes->createDraftVersion(
                    actor: $this->actor($request),
                    organizationId: $context->requireId(),
                    publicId: $driverPriceList,
                    data: $request->validated(),
                ),
            ),
            'Driver price-list draft version created.',
            201,
        );
    }

    public function updateVersion(
        UpdateDriverPriceListVersionRequest $request,
        OrganizationContext $context,
        string $driverPriceList,
        string $version,
    ): JsonResponse {
        return $this->success(
            new DriverPriceListVersionResource(
                $this->writes->updateDraftVersion(
                    actor: $this->actor($request),
                    organizationId: $context->requireId(),
                    publicId: $driverPriceList,
                    versionNumber: (int) $version,
                    data: $request->validated(),
                ),
            ),
            'Driver price-list draft version updated.',
        );
    }

    public function approveVersion(
        ApproveDriverPriceListVersionRequest $request,
        OrganizationContext $context,
        string $driverPriceList,
        string $version,
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return $this->success(
            new DriverPriceListVersionResource(
                $this->writes->approveDraftVersion(
                    actor: $actor,
                    organizationId: $context->requireId(),
                    publicId: $driverPriceList,
                    versionNumber: (int) $version,
                    data: $request->validated(),
                ),
            ),
            'Driver price-list version approved.',
        );
    }

    public function activateVersion(
        ActivateDriverPriceListVersionRequest $request,
        OrganizationContext $context,
        string $driverPriceList,
        string $version,
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return $this->success(
            new DriverPriceListVersionResource(
                $this->writes->activateApprovedVersion(
                    actor: $actor,
                    organizationId: $context->requireId(),
                    publicId: $driverPriceList,
                    versionNumber: (int) $version,
                    data: $request->validated(),
                ),
            ),
            'Driver price-list version activated.',
        );
    }

    public function expireVersion(
        ExpireDriverPriceListVersionRequest $request,
        OrganizationContext $context,
        string $driverPriceList,
        string $version,
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return $this->success(
            new DriverPriceListVersionResource(
                $this->writes->expireActiveVersion(
                    actor: $actor,
                    organizationId: $context->requireId(),
                    publicId: $driverPriceList,
                    versionNumber: (int) $version,
                    data: $request->validated(),
                ),
            ),
            'Driver price-list version expired.',
        );
    }

    public function index(
        DriverPriceListIndexRequest $request,
        DriverPriceListQueryService $queries,
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        $priceLists = $queries->paginate(
            $actor,
            $request->validated(),
        );

        return $this->success([
            'items' => DriverPriceListResource::collection(
                $priceLists,
            ),
            'pagination' => [
                'current_page' => $priceLists->currentPage(),
                'last_page' => $priceLists->lastPage(),
                'per_page' => $priceLists->perPage(),
                'total' => $priceLists->total(),
            ],
        ]);
    }

    public function show(
        Request $request,
        string $driverPriceList,
        DriverPriceListQueryService $queries,
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return $this->success(
            new DriverPriceListResource(
                $queries->findByPublicId(
                    $actor,
                    $driverPriceList,
                ),
            ),
        );
    }

    public function versions(
        Request $request,
        string $driverPriceList,
        DriverPriceListQueryService $queries,
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return $this->success([
            'items' => DriverPriceListVersionResource::collection(
                $queries->versions(
                    $actor,
                    $driverPriceList,
                ),
            ),
        ]);
    }

    public function version(
        Request $request,
        string $driverPriceList,
        string $version,
        DriverPriceListQueryService $queries,
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return $this->success(
            new DriverPriceListVersionResource(
                $queries->findVersion(
                    $actor,
                    $driverPriceList,
                    (int) $version,
                ),
            ),
        );
    }

    private function actor(
        StoreDriverPriceListVersionRequest|UpdateDriverPriceListVersionRequest $request,
    ): User {
        $actor = $request->user();

        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return $actor;
    }
}
