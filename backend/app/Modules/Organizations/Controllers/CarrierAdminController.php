<?php

declare(strict_types=1);

namespace App\Modules\Organizations\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
                static function (
                    OrganizationRelationship $relationship,
                ): array {
                    $carrier = $relationship->targetOrganization;

                    return [
                        'id' => (int) $carrier->getKey(),
                        'name' => (string) $carrier->getAttribute('name'),
                        'type' => (string) $carrier->getAttribute('type'),
                        'status' => (string) $carrier->getAttribute('status'),
                        'relationship_id' => (int) $relationship->getKey(),
                    ];
                },
            )
            ->values();

        return response()->json([
            'data' => [
                'items' => $items,
            ],
        ]);
    }

    public function store(
        Request $request,
        OrganizationContext $context,
    ): JsonResponse {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $name = trim(
            (string) $validated['name'],
        );

        if ($name === '') {
            return response()->json([
                'message' => 'Název dopravce je povinný.',
                'errors' => [
                    'name' => [
                        'Název dopravce je povinný.',
                    ],
                ],
            ], 422);
        }

        $sourceOrganization = $this->masterOrganization(
            $context->requireId(),
        );

        $carrier = DB::transaction(
            static function () use (
                $sourceOrganization,
                $name,
            ): Organization {
                $carrier = Organization::query()->create([
                    'name' => $name,
                    'type' => Organization::TYPE_SUBCONTRACTOR,
                    'status' => Organization::STATUS_ACTIVE,
                ]);

                OrganizationRelationship::query()->create([
                    'source_organization_id' => (int) $sourceOrganization->getKey(),
                    'target_organization_id' => (int) $carrier->getKey(),
                    'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
                    'status' => OrganizationRelationship::STATUS_ACTIVE,
                    'valid_from' => now(),
                    'valid_until' => null,
                ]);

                return $carrier;
            },
        );

        return response()->json([
            'message' => 'Dopravce byl vytvořen.',
            'data' => $this->resource(
                $carrier,
            ),
        ], 201);
    }

    public function update(
        Request $request,
        OrganizationContext $context,
        int $carrier,
    ): JsonResponse {
        $organizationId = $context->requireId();

        $this->assertMasterOrganization(
            $organizationId,
        );

        $target = $this->findScopedCarrier(
            $organizationId,
            $carrier,
        );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('organizations', 'name')
                    ->ignore(
                        $target->getKey(),
                    ),
            ],
        ]);

        $target->setAttribute(
            'name',
            trim(
                (string) $validated['name'],
            ),
        );

        $target->save();

        return response()->json([
            'message' => 'Údaje dopravce byly upraveny.',
            'data' => $this->resource(
                $target->refresh(),
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

    /**
     * @return array<string, int|string>
     */
    private function resource(
        Organization $carrier,
    ): array {
        return [
            'id' => (int) $carrier->getKey(),
            'name' => (string) $carrier->getAttribute('name'),
            'type' => (string) $carrier->getAttribute('type'),
            'status' => (string) $carrier->getAttribute('status'),
        ];
    }
}
