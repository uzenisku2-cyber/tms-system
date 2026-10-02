<?php

declare(strict_types=1);

namespace App\Modules\Identity\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Identity\Services\OrganizationPeopleService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class OrganizationPeopleController
{
    public function __construct(private readonly OrganizationPeopleService $people) {}

    public function index(OrganizationContext $context): JsonResponse
    {
        return response()->json(['data' => $this->people->index($context->requireId())]);
    }

    public function store(Request $request, OrganizationContext $context): JsonResponse
    {
        return response()->json(['data' => $this->people->create(
            $context->requireId(), $this->actor($request), $this->validatePerson($request),
        )], 201);
    }

    public function updateRoles(Request $request, OrganizationContext $context, int $user): JsonResponse
    {
        return response()->json(['data' => $this->people->replaceRoles(
            $context->requireId(), $this->actor($request), $user, $this->validateRoles($request),
        )]);
    }

    public function indexCarrier(OrganizationContext $context, int $organization): JsonResponse
    {
        $this->assertManagedCarrier($context->requireId(), $organization);

        return response()->json(['data' => $this->people->index($organization)]);
    }

    public function updateCarrierRoles(Request $request, OrganizationContext $context, int $organization, int $user): JsonResponse
    {
        $this->assertManagedCarrier($context->requireId(), $organization);

        return response()->json(['data' => $this->people->replaceRoles(
            $organization, $this->actor($request), $user, $this->validateRoles($request),
        )]);
    }

    public function storeCarrier(Request $request, OrganizationContext $context, int $organization): JsonResponse
    {
        $this->assertManagedCarrier($context->requireId(), $organization);

        return response()->json(['data' => $this->people->create(
            $organization, $this->actor($request), $this->validatePerson($request),
        )], 201);
    }

    private function assertManagedCarrier(int $masterId, int $organization): void
    {
        abort_unless(Organization::query()->whereKey($masterId)->value('type') === Organization::TYPE_MASTER, 403);
        $allowed = OrganizationRelationship::query()
            ->where('source_organization_id', $masterId)
            ->where('target_organization_id', $organization)
            ->where('relationship_type', OrganizationRelationship::TYPE_SUBCONTRACTING)
            ->where('status', OrganizationRelationship::STATUS_ACTIVE)
            ->where(static function ($query): void {
                $query->whereNull('valid_from')->orWhere('valid_from', '<=', now());
            })
            ->where(static function ($query): void {
                $query->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })
            ->whereHas('targetOrganization', static function ($query): void {
                $query->where('type', Organization::TYPE_SUBCONTRACTOR)
                    ->where('status', Organization::STATUS_ACTIVE);
            })->exists();
        abort_unless($allowed, 404);
    }

    /** @return list<string> */
    private function validateRoles(Request $request): array
    {
        $validated = $request->validate([
            'roles' => ['required', 'array', 'min:1', 'max:3'],
            'roles.*' => ['required', 'string', 'distinct', Rule::in(['carrier-admin', 'dispatcher', 'driver'])],
        ]);

        $roles = $validated['roles'];
        if (! is_array($roles)) {
            abort(422);
        }

        return array_values(array_map(static fn (mixed $role): string => (string) $role, $roles));
    }

    /** @return array<string, mixed> */
    private function validatePerson(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'roles' => ['required', 'array', 'min:1', 'max:3'],
            'roles.*' => ['required', 'string', 'distinct', Rule::in(['carrier-admin', 'dispatcher', 'driver'])],
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
