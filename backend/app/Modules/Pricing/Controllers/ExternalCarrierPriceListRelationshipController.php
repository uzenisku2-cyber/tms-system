<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Requests\ActivatePriceListVersionRequest;
use App\Modules\Pricing\Requests\ApprovePriceListVersionRequest;
use App\Modules\Pricing\Requests\ExpirePriceListVersionRequest;
use App\Modules\Pricing\Requests\StoreCustomerManagedPriceListRequest;
use App\Modules\Pricing\Requests\StoreCustomerManagedPriceListVersionRequest;
use App\Modules\Pricing\Requests\UpdatePriceListVersionRequest;
use App\Modules\Pricing\Resources\PriceListResource;
use App\Modules\Pricing\Resources\PriceListVersionResource;
use App\Modules\Pricing\Services\PriceListWriteService;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use LogicException;

final class ExternalCarrierPriceListRelationshipController extends Controller
{
    public function index(
        OrganizationContext $organizationContext,
    ): JsonResponse {
        $customerOrganizationId =
            $organizationContext->requireId();

        $effectiveAt = now();

        $relationships =
            $this->relationshipQuery(
                $customerOrganizationId,
            )
                ->orderBy('id')
                ->get()
                ->filter(
                    static fn (
                        OrganizationRelationship $relationship,
                    ): bool => $relationship->isActiveAt(
                        $effectiveAt,
                    ),
                );

        return response()->json([
            'data' => $relationships
                ->map(
                    fn (
                        OrganizationRelationship $relationship,
                    ): array => $this->resource(
                        $relationship,
                        $customerOrganizationId,
                    ),
                )
                ->values(),
        ]);
    }

    public function show(
        OrganizationContext $organizationContext,
        int $relationship,
    ): JsonResponse {
        $customerOrganizationId =
            $organizationContext->requireId();

        $relationshipModel =
            $this->relationshipQuery(
                $customerOrganizationId,
            )
                ->whereKey($relationship)
                ->firstOrFail();

        abort_unless(
            $relationshipModel->isActiveAt(now()),
            404,
        );

        return response()->json([
            'data' => $this->resource(
                $relationshipModel,
                $customerOrganizationId,
            ),
        ]);
    }

    public function store(
        StoreCustomerManagedPriceListRequest $request,
        PriceListWriteService $writes,
        int $relationship,
    ): JsonResponse {
        $actor = $request->user();

        abort_unless(
            $actor instanceof User,
            401,
        );

        $priceList = $writes->createCustomerManagedDraft(
            $actor,
            $relationship,
            $request->validated(),
        );

        return response()->json(
            [
                'data' => (new PriceListResource(
                    $priceList,
                ))->resolve($request),
            ],
            201,
        );
    }

    public function storeVersion(
        StoreCustomerManagedPriceListVersionRequest $request,
        PriceListWriteService $writes,
        int $relationship,
        string $priceList,
    ): JsonResponse {
        $version = $writes->createCustomerManagedDraftVersion(
            $this->actor($request),
            $relationship,
            $priceList,
            $request->validated(),
        );

        return $this->versionResponse(
            $request,
            $version,
            201,
        );
    }

    public function updateVersion(
        UpdatePriceListVersionRequest $request,
        PriceListWriteService $writes,
        int $relationship,
        string $priceList,
        int $version,
    ): JsonResponse {
        return $this->versionResponse(
            $request,
            $writes->updateCustomerManagedDraftVersion(
                $this->actor($request),
                $relationship,
                $priceList,
                $version,
                $request->validated(),
            ),
        );
    }

    public function approveVersion(
        ApprovePriceListVersionRequest $request,
        PriceListWriteService $writes,
        int $relationship,
        string $priceList,
        int $version,
    ): JsonResponse {
        return $this->versionResponse(
            $request,
            $writes->approveCustomerManagedDraftVersion(
                $this->actor($request),
                $relationship,
                $priceList,
                $version,
                $request->validated(),
            ),
        );
    }

    public function activateVersion(
        ActivatePriceListVersionRequest $request,
        PriceListWriteService $writes,
        int $relationship,
        string $priceList,
        int $version,
    ): JsonResponse {
        return $this->versionResponse(
            $request,
            $writes->activateCustomerManagedApprovedVersion(
                $this->actor($request),
                $relationship,
                $priceList,
                $version,
                $request->validated(),
            ),
        );
    }

    public function expireVersion(
        ExpirePriceListVersionRequest $request,
        PriceListWriteService $writes,
        int $relationship,
        string $priceList,
        int $version,
    ): JsonResponse {
        return $this->versionResponse(
            $request,
            $writes->expireCustomerManagedActiveVersion(
                $this->actor($request),
                $relationship,
                $priceList,
                $version,
                $request->validated(),
            ),
        );
    }

    private function actor(
        StoreCustomerManagedPriceListVersionRequest|UpdatePriceListVersionRequest|ApprovePriceListVersionRequest|ActivatePriceListVersionRequest|ExpirePriceListVersionRequest $request,
    ): User {
        $actor = $request->user();

        abort_unless(
            $actor instanceof User,
            401,
        );

        return $actor;
    }

    private function versionResponse(
        StoreCustomerManagedPriceListVersionRequest|UpdatePriceListVersionRequest|ApprovePriceListVersionRequest|ActivatePriceListVersionRequest|ExpirePriceListVersionRequest $request,
        mixed $version,
        int $status = 200,
    ): JsonResponse {
        return response()->json(
            [
                'data' => (new PriceListVersionResource(
                    $version,
                ))->resolve($request),
            ],
            $status,
        );
    }

    /** @return Builder<OrganizationRelationship> */
    private function relationshipQuery(
        int $customerOrganizationId,
    ): Builder {
        return OrganizationRelationship::query()
            ->with('targetOrganization')
            ->where(
                'source_organization_id',
                $customerOrganizationId,
            )
            ->where(
                'relationship_type',
                OrganizationRelationship::TYPE_SUBCONTRACTING,
            )
            ->whereHas(
                'targetOrganization',
                static function ($query): void {
                    $query
                        ->where(
                            'type',
                            Organization::TYPE_SUBCONTRACTOR,
                        )
                        ->where(
                            'status',
                            Organization::STATUS_ACTIVE,
                        );
                },
            );
    }

    /** @return array<string, mixed> */
    private function resource(
        OrganizationRelationship $relationship,
        int $customerOrganizationId,
    ): array {
        $externalCarrier =
            $relationship->targetOrganization;

        if (! $externalCarrier instanceof Organization) {
            throw new LogicException(
                'The external-carrier relationship has no target organization.',
            );
        }

        $relationshipId =
            (int) $relationship->getKey();

        $priceLists = PriceList::query()
            ->with([
                'versions' => static function ($query): void {
                    $query
                        ->with([
                            'items',
                            'conditionalRules.metricComponents',
                            'conditionalRules.bands',
                        ])
                        ->orderByDesc('version_number');
                },
            ])
            ->where(
                'organization_relationship_id',
                $relationshipId,
            )
            ->where(
                'customer_organization_id',
                $customerOrganizationId,
            )
            ->where(
                'provider_organization_id',
                (int) $externalCarrier->getKey(),
            )
            ->where(
                'owner_organization_id',
                $customerOrganizationId,
            )
            ->where(
                'managed_by_organization_id',
                $customerOrganizationId,
            )
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return [
            'relationship_id' => $relationshipId,
            'relationship_type' => (string) $relationship->getAttribute(
                'relationship_type',
            ),
            'relationship_status' => (string) $relationship->getAttribute(
                'status',
            ),
            'relationship_valid_from' => $this->dateValue(
                $relationship->getAttribute('valid_from'),
            ),
            'relationship_valid_until' => $this->dateValue(
                $relationship->getAttribute('valid_until'),
            ),
            'external_carrier' => [
                'name' => (string) $externalCarrier->getAttribute('name'),
                'type' => (string) $externalCarrier->getAttribute('type'),
                'status' => (string) $externalCarrier->getAttribute('status'),
                'registration_number' => $externalCarrier->getAttribute(
                    'registration_number',
                ),
                'vat_number' => $externalCarrier->getAttribute('vat_number'),
                'street' => $externalCarrier->getAttribute('street'),
                'city' => $externalCarrier->getAttribute('city'),
                'postal_code' => $externalCarrier->getAttribute('postal_code'),
                'country_code' => $externalCarrier->getAttribute('country_code'),
            ],
            'price_lists' => $priceLists
                ->map(
                    function (PriceList $priceList): array {
                        return [
                            'public_id' => (string) $priceList->getAttribute(
                                'public_id',
                            ),
                            'code' => (string) $priceList->getAttribute('code'),
                            'name' => (string) $priceList->getAttribute('name'),
                            'currency' => (string) $priceList->getAttribute(
                                'currency',
                            ),
                            'status' => (string) $priceList->getAttribute('status'),
                            'current_version' => (int) $priceList->getAttribute(
                                'current_version',
                            ),
                            'managed_by_customer' => (
                                (int) $priceList->getAttribute(
                                    'managed_by_organization_id',
                                ) ===
                                (int) $priceList->getAttribute(
                                    'customer_organization_id',
                                )
                            ),
                            'versions' => PriceListVersionResource::collection(
                                $priceList->versions,
                            )->resolve(request()),
                        ];
                    },
                )
                ->values(),
        ];
    }

    private function dateValue(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value) && $value !== '') {
            return $value;
        }

        return null;
    }
}
