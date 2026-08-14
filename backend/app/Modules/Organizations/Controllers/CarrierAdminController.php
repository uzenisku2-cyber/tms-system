<?php

declare(strict_types=1);

namespace App\Modules\Organizations\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Organizations\Services\AresEconomicSubjectService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class CarrierAdminController
{
    public function index(
        OrganizationContext $context,
    ): JsonResponse {
        $organizationId = $context->requireId();

        $this->assertMasterOrganization(
            $organizationId,
        );

        $items = OrganizationRelationship::query()
            ->with('targetOrganization')
            ->where(
                'source_organization_id',
                $organizationId,
            )
            ->where(
                'relationship_type',
                OrganizationRelationship::TYPE_SUBCONTRACTING,
            )
            ->where(
                'status',
                OrganizationRelationship::STATUS_ACTIVE,
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
            )
            ->orderByDesc('id')
            ->get()
            ->map(
                function (
                    OrganizationRelationship $relationship,
                ): array {
                    $carrier = $relationship->targetOrganization;

                    return array_merge(
                        $this->resource($carrier),
                        [
                            'relationship_id' => (int) $relationship->getKey(),
                            'relationship_valid_from' => $relationship->getAttribute(
                                'valid_from',
                            )?->toDateString(),
                            'relationship_valid_until' => $relationship->getAttribute(
                                'valid_until',
                            )?->toDateString(),
                        ],
                    );
                },
            )
            ->values();

        return response()->json([
            'data' => [
                'items' => $items,
            ],
        ]);
    }

    public function lookupAres(
        OrganizationContext $context,
        AresEconomicSubjectService $ares,
        string $ico,
    ): JsonResponse {
        $this->assertMasterOrganization(
            $context->requireId(),
        );

        return response()->json([
            'data' => $ares->lookup($ico),
        ]);
    }

    public function store(
        Request $request,
        OrganizationContext $context,
        AresEconomicSubjectService $ares,
    ): JsonResponse {
        $validated = $request->validate([
            'registration_number' => [
                'required',
                'string',
                'regex:/^\d{8}$/',
                Rule::unique(
                    'organizations',
                    'registration_number',
                ),
            ],
            'relationship_valid_from' => [
                'required',
                'date_format:Y-m-d',
            ],
            'manual_entry' => [
                'sometimes',
                'boolean',
            ],
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'vat_status' => [
                'nullable',
                Rule::in(Organization::VAT_STATUSES),
            ],
            'vat_number' => [
                'nullable',
                'string',
                'max:32',
            ],
            'street' => [
                'nullable',
                'string',
                'max:255',
            ],
            'city' => [
                'nullable',
                'string',
                'max:100',
            ],
            'postal_code' => [
                'nullable',
                'string',
                'max:32',
            ],
            'country_code' => [
                'nullable',
                'string',
                'size:2',
            ],
        ]);

        $sourceOrganization =
            $this->masterOrganization(
                $context->requireId(),
            );

        $registrationNumber = trim(
            (string) $validated['registration_number'],
        );

        $relationshipValidFrom = CarbonImmutable::createFromFormat(
            'Y-m-d',
            (string) $validated['relationship_valid_from'],
        )->startOfDay();

        $manualEntry =
            (bool) ($validated['manual_entry'] ?? false);

        if ($manualEntry) {
            $aresLookupSucceeded = false;

            try {
                $ares->lookup(
                    $registrationNumber,
                );

                $aresLookupSucceeded = true;
            } catch (ValidationException) {
                // Ruční zadání je fallback pouze tehdy,
                // když aktuální ověření v ARES neuspěje.
            }

            if ($aresLookupSucceeded) {
                throw ValidationException::withMessages([
                    'registration_number' => [
                        'IČO je nyní možné ověřit v ARES. Použijte ověřené údaje.',
                    ],
                ]);
            }

            $name = trim(
                (string) ($validated['name'] ?? ''),
            );

            if ($name === '') {
                throw ValidationException::withMessages([
                    'name' => [
                        'Název dopravce je při ručním zadání povinný.',
                    ],
                ]);
            }

            $vatStatus =
                (string) ($validated['vat_status'] ?? '');

            if (
                ! in_array(
                    $vatStatus,
                    Organization::VAT_STATUSES,
                    true,
                )
            ) {
                throw ValidationException::withMessages([
                    'vat_status' => [
                        'Vyberte, zda je dopravce plátce nebo neplátce DPH.',
                    ],
                ]);
            }

            $vatNumber = strtoupper(
                trim(
                    (string) ($validated['vat_number'] ?? ''),
                ),
            );

            if ($vatNumber === '') {
                $vatNumber = null;
            }

            if (
                $vatStatus === Organization::VAT_STATUS_PAYER
                && $vatNumber === null
            ) {
                throw ValidationException::withMessages([
                    'vat_number' => [
                        'DIČ je u plátce DPH povinné.',
                    ],
                ]);
            }

            $aresData = [
                'registration_number' => $registrationNumber,
                'name' => $name,
                'vat_number' => $vatNumber,
                'vat_status' => $vatStatus,
                'street' => $this->nullableTrimmed(
                    $validated['street'] ?? null,
                ),
                'city' => $this->nullableTrimmed(
                    $validated['city'] ?? null,
                ),
                'postal_code' => $this->nullableTrimmed(
                    $validated['postal_code'] ?? null,
                ),
                'country_code' => strtoupper(
                    $this->nullableTrimmed(
                        $validated['country_code'] ?? null,
                    ) ?? 'CZ',
                ),
            ];

            $aresVerifiedAt = null;
        } else {
            $aresData =
                $ares->lookup($registrationNumber);

            $aresVerifiedAt = now();
        }

        $carrier = DB::transaction(
            static function () use (
                $sourceOrganization,
                $aresData,
                $aresVerifiedAt,
                $relationshipValidFrom,
            ): Organization {
                $carrier = Organization::query()->create([
                    'name' => $aresData['name'],
                    'type' => Organization::TYPE_SUBCONTRACTOR,
                    'status' => Organization::STATUS_ACTIVE,
                    'registration_number' => $aresData['registration_number'],
                    'vat_number' => $aresData['vat_number'],
                    'vat_status' => $aresData['vat_status'],
                    'ares_verified_at' => $aresVerifiedAt,
                    'street' => $aresData['street'],
                    'city' => $aresData['city'],
                    'postal_code' => $aresData['postal_code'],
                    'country_code' => $aresData['country_code'],
                ]);

                OrganizationRelationship::query()->create([
                    'source_organization_id' => (int) $sourceOrganization->getKey(),
                    'target_organization_id' => (int) $carrier->getKey(),
                    'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
                    'status' => OrganizationRelationship::STATUS_ACTIVE,
                    'valid_from' => $relationshipValidFrom,
                    'valid_until' => null,
                ]);

                return $carrier;
            },
        );

        return response()->json([
            'message' => $manualEntry
                ? 'Dopravce byl uložen ručně a čeká na ověření v ARES.'
                : 'Dopravce byl vytvořen a ověřen v ARES.',
            'data' => $this->resource($carrier),
        ], 201);
    }

    public function verifyAres(
        OrganizationContext $context,
        AresEconomicSubjectService $ares,
        int $carrier,
    ): JsonResponse {
        $organizationId =
            $context->requireId();

        $this->assertMasterOrganization(
            $organizationId,
        );

        $target =
            $this->findScopedCarrier(
                $organizationId,
                $carrier,
            );

        $registrationNumber = trim(
            (string) $target->getAttribute(
                'registration_number',
            ),
        );

        if ($registrationNumber === '') {
            throw ValidationException::withMessages([
                'registration_number' => [
                    'Dopravce nemá uložené IČO.',
                ],
            ]);
        }

        $aresData =
            $ares->lookup($registrationNumber);

        DB::transaction(
            static function () use (
                $target,
                $aresData,
            ): void {
                $target->forceFill([
                    'name' => $aresData['name'],
                    'vat_number' => $aresData['vat_number'],
                    'vat_status' => $aresData['vat_status'],
                    'street' => $aresData['street'],
                    'city' => $aresData['city'],
                    'postal_code' => $aresData['postal_code'],
                    'country_code' => $aresData['country_code'],
                    'ares_verified_at' => now(),
                ])->save();
            },
        );

        return response()->json([
            'message' => 'Dopravce byl úspěšně ověřen v ARES.',
            'data' => $this->resource(
                $target->refresh(),
            ),
        ]);
    }

    public function update(
        Request $request,
        OrganizationContext $context,
        AresEconomicSubjectService $ares,
        int $carrier,
    ): JsonResponse {
        $organizationId =
            $context->requireId();

        $this->assertMasterOrganization(
            $organizationId,
        );

        $target =
            $this->findScopedCarrier(
                $organizationId,
                $carrier,
            );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'vat_status' => [
                'required',
                Rule::in(
                    Organization::VAT_STATUSES,
                ),
            ],
            'vat_number' => [
                'nullable',
                'string',
                'max:32',
            ],
            'street' => [
                'nullable',
                'string',
                'max:255',
            ],
            'city' => [
                'nullable',
                'string',
                'max:100',
            ],
            'postal_code' => [
                'nullable',
                'string',
                'max:32',
            ],
            'country_code' => [
                'nullable',
                'string',
                'size:2',
            ],
            'relationship_valid_from' => [
                'required',
                'date_format:Y-m-d',
            ],
            'verify_with_ares' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $relationship =
            OrganizationRelationship::query()
                ->where(
                    'source_organization_id',
                    $organizationId,
                )
                ->where(
                    'target_organization_id',
                    $target->getKey(),
                )
                ->where(
                    'relationship_type',
                    OrganizationRelationship::TYPE_SUBCONTRACTING,
                )
                ->where(
                    'status',
                    OrganizationRelationship::STATUS_ACTIVE,
                )
                ->firstOrFail();

        $verifyWithAres =
            (bool) (
                $validated['verify_with_ares']
                ?? false
            );

        $registrationNumber = trim(
            (string) $target->getAttribute(
                'registration_number',
            ),
        );

        if ($registrationNumber === '') {
            throw ValidationException::withMessages([
                'registration_number' => [
                    'Dopravce nemá uložené IČO.',
                ],
            ]);
        }

        if ($verifyWithAres) {
            $aresData =
                $ares->lookup(
                    $registrationNumber,
                );

            $newData = [
                'name' => $aresData['name'],
                'vat_status' => $aresData['vat_status'],
                'vat_number' => $aresData['vat_number'],
                'street' => $aresData['street'],
                'city' => $aresData['city'],
                'postal_code' => $aresData['postal_code'],
                'country_code' => $aresData['country_code'],
            ];

            $aresVerifiedAt = now();
        } else {
            $vatStatus =
                (string) $validated['vat_status'];

            $vatNumber = strtoupper(
                trim(
                    (string) (
                        $validated['vat_number']
                        ?? ''
                    ),
                ),
            );

            if ($vatNumber === '') {
                $vatNumber = null;
            }

            if (
                $vatStatus ===
                    Organization::VAT_STATUS_PAYER
                && $vatNumber === null
            ) {
                throw ValidationException::withMessages([
                    'vat_number' => [
                        'DIČ je u plátce DPH povinné.',
                    ],
                ]);
            }

            $newData = [
                'name' => trim(
                    (string) $validated['name'],
                ),
                'vat_status' => $vatStatus,
                'vat_number' => $vatNumber,
                'street' => $this->nullableTrimmed(
                    $validated['street']
                    ?? null,
                ),
                'city' => $this->nullableTrimmed(
                    $validated['city']
                    ?? null,
                ),
                'postal_code' => $this->nullableTrimmed(
                    $validated['postal_code']
                    ?? null,
                ),
                'country_code' => strtoupper(
                    $this->nullableTrimmed(
                        $validated['country_code']
                        ?? null,
                    ) ?? 'CZ',
                ),
            ];

            $aresFieldsChanged = false;

            foreach (
                [
                    'name',
                    'vat_status',
                    'vat_number',
                    'street',
                    'city',
                    'postal_code',
                    'country_code',
                ] as $field
            ) {
                $current =
                    $target->getAttribute($field);

                $next =
                    $newData[$field];

                $currentNormalized =
                    $current === null
                        ? null
                        : (string) $current;

                $nextNormalized =
                    $next === null
                        ? null
                        : (string) $next;

                if (
                    $currentNormalized !==
                    $nextNormalized
                ) {
                    $aresFieldsChanged = true;
                    break;
                }
            }

            $aresVerifiedAt =
                $aresFieldsChanged
                    ? null
                    : $target->getAttribute(
                        'ares_verified_at',
                    );
        }

        $relationshipValidFrom =
            CarbonImmutable::createFromFormat(
                'Y-m-d',
                (string) $validated[
                    'relationship_valid_from'
                ],
            )->startOfDay();

        DB::transaction(
            static function () use (
                $target,
                $relationship,
                $newData,
                $aresVerifiedAt,
                $relationshipValidFrom,
            ): void {
                $target->forceFill([
                    ...$newData,
                    'ares_verified_at' => $aresVerifiedAt,
                ])->save();

                $relationship->setAttribute(
                    'valid_from',
                    $relationshipValidFrom,
                );

                $relationship->save();
            },
        );

        $target->refresh();
        $relationship->refresh();

        return response()->json([
            'message' => $verifyWithAres
                ? 'Údaje dopravce byly prověřeny v ARES a uloženy.'
                : (
                    $target->getAttribute(
                        'ares_verified_at',
                    ) === null
                        ? 'Údaje dopravce byly uloženy. Dopravce čeká na nové ověření v ARES.'
                        : 'Údaje dopravce byly uloženy.'
                ),
            'data' => array_merge(
                $this->resource($target),
                [
                    'relationship_id' => (int) $relationship->getKey(),
                    'relationship_valid_from' => $relationship->getAttribute(
                        'valid_from',
                    )?->toDateString(),
                    'relationship_valid_until' => $relationship->getAttribute(
                        'valid_until',
                    )?->toDateString(),
                ],
            ),
        ]);
    }

    private function findScopedCarrier(
        int $organizationId,
        int $carrierId,
    ): Organization {
        return Organization::query()
            ->whereKey($carrierId)
            ->where(
                'type',
                Organization::TYPE_SUBCONTRACTOR,
            )
            ->where(
                'status',
                Organization::STATUS_ACTIVE,
            )
            ->whereHas(
                'incomingRelationships',
                static function ($query) use (
                    $organizationId,
                ): void {
                    $query
                        ->where(
                            'source_organization_id',
                            $organizationId,
                        )
                        ->where(
                            'relationship_type',
                            OrganizationRelationship::TYPE_SUBCONTRACTING,
                        )
                        ->where(
                            'status',
                            OrganizationRelationship::STATUS_ACTIVE,
                        );
                },
            )
            ->firstOrFail();
    }

    private function assertMasterOrganization(
        int $organizationId,
    ): void {
        $this->masterOrganization(
            $organizationId,
        );
    }

    private function masterOrganization(
        int $organizationId,
    ): Organization {
        $organization = Organization::query()
            ->whereKey($organizationId)
            ->where(
                'status',
                Organization::STATUS_ACTIVE,
            )
            ->firstOrFail();

        if (
            $organization->getAttribute('type')
            !== Organization::TYPE_MASTER
        ) {
            abort(
                403,
                'Carrier administration requires master organization context.',
            );
        }

        return $organization;
    }

    private function nullableTrimmed(
        mixed $value,
    ): ?string {
        $value = trim(
            (string) ($value ?? ''),
        );

        return $value === ''
            ? null
            : $value;
    }

    /**
     * @return array<string, int|string|null>
     */
    private function resource(
        Organization $carrier,
    ): array {
        $aresVerifiedAt = $carrier->getAttribute(
            'ares_verified_at',
        );

        return [
            'id' => (int) $carrier->getKey(),
            'name' => (string) $carrier->getAttribute('name'),
            'type' => (string) $carrier->getAttribute('type'),
            'status' => (string) $carrier->getAttribute('status'),
            'registration_number' => $carrier->getAttribute(
                'registration_number',
            ),
            'vat_number' => $carrier->getAttribute(
                'vat_number',
            ),
            'vat_status' => $carrier->getAttribute(
                'vat_status',
            ),
            'street' => $carrier->getAttribute('street'),
            'city' => $carrier->getAttribute('city'),
            'postal_code' => $carrier->getAttribute(
                'postal_code',
            ),
            'country_code' => $carrier->getAttribute(
                'country_code',
            ),
            'ares_verified_at' => $aresVerifiedAt?->toIso8601String(),
        ];
    }
}
