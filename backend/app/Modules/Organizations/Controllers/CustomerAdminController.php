<?php

declare(strict_types=1);

namespace App\Modules\Organizations\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Organizations\Services\AresEconomicSubjectService;
use App\Modules\Pricing\Models\PriceList;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

final class CustomerAdminController extends Controller
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
    ) {}

    public function index(): JsonResponse
    {
        $providerOrganizationId =
            $this->organizationContext->requireId();

        $effectiveAt = now();

        $relationships =
            OrganizationRelationship::query()
                ->with('sourceOrganization')
                ->where(
                    'target_organization_id',
                    $providerOrganizationId,
                )
                ->where(
                    'relationship_type',
                    OrganizationRelationship::TYPE_SUBCONTRACTING,
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
                        $providerOrganizationId,
                    ),
                )
                ->values(),
        ]);
    }

    public function store(
        Request $request,
        AresEconomicSubjectService $ares,
    ): JsonResponse {
        $providerOrganizationId =
            $this->organizationContext->requireId();

        $validated = $request->validate([
            'registration_number' => [
                'required',
                'string',
                'regex:/^\d{8}$/',
            ],
            'relationship_valid_from' => [
                'required',
                'date_format:Y-m-d',
            ],
        ]);

        $registrationNumber = trim(
            (string) $validated['registration_number'],
        );

        $relationshipValidFrom =
            CarbonImmutable::createFromFormat(
                'Y-m-d',
                (string) $validated['relationship_valid_from'],
            )->startOfDay();

        $knownCustomer =
            Organization::query()
                ->where(
                    'registration_number',
                    $registrationNumber,
                )
                ->first();

        $aresData =
            $knownCustomer instanceof Organization
                ? null
                : $ares->lookup($registrationNumber);

        $relationship = DB::transaction(
            function () use (
                $providerOrganizationId,
                $registrationNumber,
                $relationshipValidFrom,
                $aresData,
            ): OrganizationRelationship {
                $provider =
                    Organization::query()
                        ->whereKey($providerOrganizationId)
                        ->lockForUpdate()
                        ->firstOrFail();

                if (! $provider->isActive()) {
                    throw ValidationException::withMessages([
                        'organization' => [
                            'Poskytovatel musí být aktivní organizace.',
                        ],
                    ]);
                }

                $customer =
                    Organization::query()
                        ->where(
                            'registration_number',
                            $registrationNumber,
                        )
                        ->lockForUpdate()
                        ->first();

                if (! $customer instanceof Organization) {
                    if (! is_array($aresData)) {
                        throw new LogicException(
                            'ARES data are missing for a new customer.',
                        );
                    }

                    $customer =
                        Organization::query()->create([
                            'name' => $aresData['name'],
                            'type' => Organization::TYPE_CARRIER,
                            'status' => Organization::STATUS_ACTIVE,
                            'registration_number' => $aresData['registration_number'],
                            'vat_number' => $aresData['vat_number'],
                            'vat_status' => $aresData['vat_status'],
                            'ares_verified_at' => now(),
                            'street' => $aresData['street'],
                            'city' => $aresData['city'],
                            'postal_code' => $aresData['postal_code'],
                            'country_code' => $aresData['country_code'],
                        ]);
                }

                if (! $customer->isActive()) {
                    throw ValidationException::withMessages([
                        'registration_number' => [
                            'Odběratel musí být aktivní organizace.',
                        ],
                    ]);
                }

                if (
                    (int) $customer->getKey() ===
                    $providerOrganizationId
                ) {
                    throw ValidationException::withMessages([
                        'registration_number' => [
                            'Organizace nemůže být vlastním odběratelem.',
                        ],
                    ]);
                }

                $existingRelationship =
                    OrganizationRelationship::query()
                        ->where(
                            'source_organization_id',
                            $customer->getKey(),
                        )
                        ->where(
                            'target_organization_id',
                            $providerOrganizationId,
                        )
                        ->where(
                            'relationship_type',
                            OrganizationRelationship::TYPE_SUBCONTRACTING,
                        )
                        ->where(
                            'status',
                            '!=',
                            OrganizationRelationship::STATUS_ENDED,
                        )
                        ->lockForUpdate()
                        ->first();

                if (
                    $existingRelationship instanceof OrganizationRelationship
                ) {
                    throw ValidationException::withMessages([
                        'registration_number' => [
                            'Aktivní nebo pozastavený vztah s odběratelem již existuje.',
                        ],
                    ]);
                }

                $relationship =
                    OrganizationRelationship::query()->create([
                        'source_organization_id' => (int) $customer->getKey(),
                        'target_organization_id' => $providerOrganizationId,
                        'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
                        'status' => OrganizationRelationship::STATUS_ACTIVE,
                        'valid_from' => $relationshipValidFrom,
                        'valid_until' => null,
                    ]);

                $relationship->load(
                    'sourceOrganization',
                );

                return $relationship;
            },
            3,
        );

        return response()->json(
            [
                'message' => 'Odběratel byl přidán.',
                'data' => $this->resource(
                    $relationship,
                    $providerOrganizationId,
                ),
            ],
            201,
        );
    }

    public function show(int $relationship): JsonResponse
    {
        $providerOrganizationId =
            $this->organizationContext->requireId();

        $relationshipModel =
            OrganizationRelationship::query()
                ->with('sourceOrganization')
                ->whereKey($relationship)
                ->where(
                    'target_organization_id',
                    $providerOrganizationId,
                )
                ->where(
                    'relationship_type',
                    OrganizationRelationship::TYPE_SUBCONTRACTING,
                )
                ->firstOrFail();

        return response()->json([
            'data' => $this->resource(
                $relationshipModel,
                $providerOrganizationId,
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resource(
        OrganizationRelationship $relationship,
        int $providerOrganizationId,
    ): array {
        $customer = $relationship->sourceOrganization;

        if (! $customer instanceof Organization) {
            throw new LogicException(
                'The customer relationship has no source organization.',
            );
        }

        $relationshipId = (int) $relationship->getKey();

        $priceLists = PriceList::query()
            ->with([
                'versions' => static function ($query): void {
                    $query->orderByDesc('version_number');
                },
            ])
            ->where(
                'organization_relationship_id',
                $relationshipId,
            )
            ->where(
                'customer_organization_id',
                (int) $customer->getKey(),
            )
            ->where(
                'provider_organization_id',
                $providerOrganizationId,
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
            'customer' => [
                'id' => (int) $customer->getKey(),
                'name' => (string) $customer->getAttribute('name'),
                'type' => (string) $customer->getAttribute('type'),
                'status' => (string) $customer->getAttribute('status'),
                'registration_number' => $customer->getAttribute('registration_number'),
                'vat_number' => $customer->getAttribute('vat_number'),
                'street' => $customer->getAttribute('street'),
                'city' => $customer->getAttribute('city'),
                'postal_code' => $customer->getAttribute('postal_code'),
                'country_code' => $customer->getAttribute('country_code'),
            ],
            'price_lists' => $priceLists
                ->map(
                    function (
                        PriceList $priceList,
                    ) use (
                        $providerOrganizationId,
                    ): array {
                        return [
                            'public_id' => (string) $priceList->getAttribute(
                                'public_id',
                            ),
                            'code' => (string) $priceList->getAttribute(
                                'code',
                            ),
                            'name' => (string) $priceList->getAttribute(
                                'name',
                            ),
                            'currency' => (string) $priceList->getAttribute(
                                'currency',
                            ),
                            'status' => (string) $priceList->getAttribute(
                                'status',
                            ),
                            'current_version' => (int) $priceList->getAttribute(
                                'current_version',
                            ),
                            'managed_by_provider' => (int) $priceList->getAttribute(
                                'managed_by_organization_id',
                            ) === $providerOrganizationId,
                            'versions' => $priceList->versions
                                ->map(
                                    fn ($version): array => [
                                        'version_number' => (int) $version->getAttribute(
                                            'version_number',
                                        ),
                                        'status' => (string) $version->getAttribute(
                                            'status',
                                        ),
                                        'valid_from' => $this->dateValue(
                                            $version->getAttribute('valid_from'),
                                        ),
                                        'valid_until' => $this->dateValue(
                                            $version->getAttribute('valid_until'),
                                        ),
                                    ],
                                )
                                ->values(),
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
