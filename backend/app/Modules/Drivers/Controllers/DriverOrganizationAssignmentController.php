<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Organizations\Models\Organization;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DriverOrganizationAssignmentController
{
    public function __construct(
        private readonly DriverSupervisoryAuthorizationService $authorizationService,
    ) {}

    public function index(
        Request $request,
        OrganizationContext $context,
        int $driver,
    ): JsonResponse {
        $masterOrganizationId = $context->requireId();

        $this->assertMasterOrganization(
            $masterOrganizationId,
        );

        $actor = $this->actor(
            $request,
        );

        $driverModel = $this->authorizationService->findVisibleDriver(
            actor: $actor,
            organizationId: $masterOrganizationId,
            driverId: $driver,
        );

        $items = DriverOrganizationAssignment::query()
            ->with('organization')
            ->where(
                'driver_id',
                $driverModel->getKey(),
            )
            ->orderByDesc('valid_from')
            ->orderByDesc('id')
            ->get()
            ->map(
                fn (
                    DriverOrganizationAssignment $assignment,
                ): array => $this->resource(
                    $assignment,
                ),
            )
            ->values();

        $today = CarbonImmutable::today();

        $current = $items->first(
            static function (array $item) use ($today): bool {
                if ($item['valid_from'] > $today->toDateString()) {
                    return false;
                }

                return $item['valid_until'] === null
                    || $item['valid_until'] >= $today->toDateString();
            },
        );

        return response()->json([
            'data' => [
                'current' => $current,
                'items' => $items,
            ],
        ]);
    }

    public function store(
        Request $request,
        OrganizationContext $context,
        int $driver,
    ): JsonResponse {
        $validated = $request->validate([
            'organization_id' => [
                'required',
                'integer',
                'min:1',
            ],
            'employment_type' => [
                'nullable',
                'in:employee,dpp,dpc,other',
            ],
            'valid_from' => [
                'required',
                'date_format:Y-m-d',
            ],
            'valid_until' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:valid_from',
            ],
            'end_reason' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $masterOrganizationId = $context->requireId();

        $employmentType = $validated['employment_type'] ?? null;

        if (
            (int) $validated['organization_id'] === $masterOrganizationId
            && $employmentType === null
        ) {
            throw ValidationException::withMessages([
                'employment_type' => [
                    'Typ pracovního vztahu je u vlastního řidiče povinný.',
                ],
            ]);
        }

        $this->assertMasterOrganization(
            $masterOrganizationId,
        );

        $actor = $this->actor(
            $request,
        );

        $driverModel = $this->authorizationService->findVisibleDriver(
            actor: $actor,
            organizationId: $masterOrganizationId,
            driverId: $driver,
        );

        $organizationId = (int) $validated['organization_id'];

        $this->authorizationService->findManageableOrganization(
            actor: $actor,
            organizationId: $masterOrganizationId,
            targetOrganizationId: $organizationId,
        );

        $validFrom = (string) $validated['valid_from'];
        $validUntil = isset($validated['valid_until'])
            && $validated['valid_until'] !== ''
                ? (string) $validated['valid_until']
                : null;

        $this->assertNoOverlap(
            (int) $driverModel->getKey(),
            $validFrom,
            $validUntil,
            null,
        );

        $assignment = DB::transaction(
            static function () use (
                $driverModel,
                $organizationId,
                $validFrom,
                $validUntil,
                $validated,
                $actor,
            ): DriverOrganizationAssignment {
                return DriverOrganizationAssignment::query()->create([
                    'driver_id' => (int) $driverModel->getKey(),
                    'organization_id' => $organizationId,
                    'employment_type' => $validated['employment_type'] ?? null,
                    'valid_from' => $validFrom,
                    'valid_until' => $validUntil,
                    'end_reason' => self::nullableTrimmed(
                        $validated['end_reason'] ?? null,
                    ),
                    'created_by_user_id' => (int) $actor->getKey(),
                    'ended_by_user_id' => $validUntil !== null
                        ? (int) $actor->getKey()
                        : null,
                ]);
            },
        );

        return response()->json([
            'message' => 'ObdobĂ­ spoluprĂˇce bylo pĹ™idĂˇno.',
            'data' => $this->resource(
                $assignment->load('organization'),
            ),
        ], 201);
    }

    public function end(
        Request $request,
        OrganizationContext $context,
        int $driver,
        int $assignment,
    ): JsonResponse {
        $validated = $request->validate([
            'valid_until' => [
                'required',
                'date_format:Y-m-d',
            ],
            'end_reason' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $masterOrganizationId = $context->requireId();

        $this->assertMasterOrganization(
            $masterOrganizationId,
        );

        $actor = $this->actor(
            $request,
        );

        $driverModel = $this->authorizationService->findVisibleDriver(
            actor: $actor,
            organizationId: $masterOrganizationId,
            driverId: $driver,
        );

        $target = DriverOrganizationAssignment::query()
            ->whereKey($assignment)
            ->where(
                'driver_id',
                $driverModel->getKey(),
            )
            ->firstOrFail();

        if ($target->getAttribute('valid_until') !== null) {
            throw ValidationException::withMessages([
                'valid_until' => [
                    'Toto obdobĂ­ uĹľ je ukonÄŤeno.',
                ],
            ]);
        }

        $validUntil = (string) $validated['valid_until'];
        $validFrom = CarbonImmutable::parse($target->valid_from)->toDateString();

        if ($validUntil < $validFrom) {
            throw ValidationException::withMessages([
                'valid_until' => [
                    'Datum ukonÄŤenĂ­ nesmĂ­ bĂ˝t pĹ™ed zaÄŤĂˇtkem spoluprĂˇce.',
                ],
            ]);
        }

        $this->assertNoOverlap(
            (int) $driverModel->getKey(),
            $validFrom,
            $validUntil,
            (int) $target->getKey(),
        );

        DB::transaction(
            static function () use (
                $target,
                $validUntil,
                $validated,
                $actor,
            ): void {
                $target->forceFill([
                    'valid_until' => $validUntil,
                    'end_reason' => self::nullableTrimmed(
                        $validated['end_reason'] ?? null,
                    ),
                    'ended_by_user_id' => (int) $actor->getKey(),
                ]);

                $target->save();
            },
        );

        return response()->json([
            'message' => 'ObdobĂ­ spoluprĂˇce bylo ukonÄŤeno.',
            'data' => $this->resource(
                $target->refresh()->load('organization'),
            ),
        ]);
    }

    public function transfer(
        Request $request,
        OrganizationContext $context,
        int $driver,
        int $assignment,
    ): JsonResponse {
        $validated = $request->validate([
            'organization_id' => [
                'required',
                'integer',
                'min:1',
            ],
            'employment_type' => [
                'nullable',
                'in:employee,dpp,dpc,other',
            ],
            'valid_from' => [
                'required',
                'date_format:Y-m-d',
            ],
            'end_reason' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $masterOrganizationId = $context->requireId();

        $this->assertMasterOrganization(
            $masterOrganizationId,
        );

        $actor = $this->actor(
            $request,
        );

        $driverModel = $this->authorizationService->findVisibleDriver(
            actor: $actor,
            organizationId: $masterOrganizationId,
            driverId: $driver,
        );

        $organizationId = (int) $validated['organization_id'];

        $employmentType = $validated['employment_type'] ?? null;

        if (
            $organizationId === $masterOrganizationId
            && $employmentType === null
        ) {
            throw ValidationException::withMessages([
                'employment_type' => [
                    'Typ pracovního vztahu je u vlastního řidiče povinný.',
                ],
            ]);
        }

        if ($organizationId !== $masterOrganizationId) {
            $employmentType = null;
        }

        $effectiveFrom = CarbonImmutable::createFromFormat(
            '!Y-m-d',
            (string) $validated['valid_from'],
        );

        $effectiveDate = $effectiveFrom->toDateString();

        $this->authorizationService->findManageableOrganization(
            actor: $actor,
            organizationId: $masterOrganizationId,
            targetOrganizationId: $organizationId,
            moment: Carbon::parse(
                $effectiveDate,
            ),
        );
        [$previousAssignment, $newAssignment] = DB::transaction(
            function () use (
                $driverModel,
                $assignment,
                $organizationId,
                $employmentType,
                $effectiveFrom,
                $effectiveDate,
                $validated,
                $actor,
            ): array {
                $assignments = DriverOrganizationAssignment::query()
                    ->where(
                        'driver_id',
                        $driverModel->getKey(),
                    )
                    ->orderBy('valid_from')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $target = $assignments->first(
                    static fn (
                        DriverOrganizationAssignment $item,
                    ): bool => (int) $item->getKey() === $assignment,
                );

                if (! $target instanceof DriverOrganizationAssignment) {
                    abort(404);
                }

                if ($target->getAttribute('valid_until') !== null) {
                    throw ValidationException::withMessages([
                        'valid_from' => [
                            'Přeřadit lze pouze aktuálně neukončené přiřazení.',
                        ],
                    ]);
                }

                $currentOrganizationId =
                    (int) $target->getAttribute('organization_id');

                if ($currentOrganizationId === $organizationId) {
                    throw ValidationException::withMessages([
                        'organization_id' => [
                            'Nový dopravce musí být jiný než současný.',
                        ],
                    ]);
                }

                $currentFrom = CarbonImmutable::parse(
                    $target->getAttribute('valid_from'),
                )->startOfDay();

                if ($effectiveFrom->lessThanOrEqualTo($currentFrom)) {
                    throw ValidationException::withMessages([
                        'valid_from' => [
                            'Datum změny musí být po začátku současného přiřazení.',
                        ],
                    ]);
                }

                $overlapExists = $assignments->contains(
                    static function (
                        DriverOrganizationAssignment $item,
                    ) use (
                        $assignment,
                        $effectiveDate,
                    ): bool {
                        if ((int) $item->getKey() === $assignment) {
                            return false;
                        }

                        $validUntil = $item->getAttribute('valid_until');

                        return $validUntil === null
                            || CarbonImmutable::parse(
                                $validUntil,
                            )->toDateString() >= $effectiveDate;
                    },
                );

                if ($overlapExists) {
                    throw ValidationException::withMessages([
                        'valid_from' => [
                            'Nové přiřazení by se překrývalo s již uloženým obdobím tohoto řidiče.',
                        ],
                    ]);
                }

                $previousUntil = $effectiveFrom
                    ->subDay()
                    ->toDateString();

                $target->forceFill([
                    'valid_until' => $previousUntil,
                    'end_reason' => self::nullableTrimmed(
                        $validated['end_reason'] ?? null,
                    ),
                    'ended_by_user_id' => (int) $actor->getKey(),
                ]);

                $target->save();

                $newAssignment = DriverOrganizationAssignment::query()
                    ->create([
                        'driver_id' => (int) $driverModel->getKey(),
                        'organization_id' => $organizationId,
                        'employment_type' => $employmentType,
                        'valid_from' => $effectiveDate,
                        'valid_until' => null,
                        'end_reason' => null,
                        'created_by_user_id' => (int) $actor->getKey(),
                        'ended_by_user_id' => null,
                    ]);

                return [
                    $target->refresh(),
                    $newAssignment,
                ];
            },
        );

        return response()->json([
            'message' => 'Přiřazení řidiče bylo změněno.',
            'data' => [
                'previous_assignment' => $this->resource(
                    $previousAssignment->load('organization'),
                ),
                'new_assignment' => $this->resource(
                    $newAssignment->load('organization'),
                ),
            ],
        ]);
    }

    private function assertNoOverlap(
        int $driverId,
        string $validFrom,
        ?string $validUntil,
        ?int $ignoreAssignmentId,
    ): void {
        $query = DriverOrganizationAssignment::query()
            ->where(
                'driver_id',
                $driverId,
            )
            ->where(
                static function (Builder $builder) use (
                    $validUntil,
                ): void {
                    if ($validUntil !== null) {
                        $builder->where(
                            'valid_from',
                            '<=',
                            $validUntil,
                        );

                        return;
                    }

                    $builder->whereNotNull(
                        'valid_from',
                    );
                },
            )
            ->where(
                static function (Builder $builder) use (
                    $validFrom,
                ): void {
                    $builder
                        ->whereNull('valid_until')
                        ->orWhere(
                            'valid_until',
                            '>=',
                            $validFrom,
                        );
                },
            );

        if ($ignoreAssignmentId !== null) {
            $query->whereKeyNot(
                $ignoreAssignmentId,
            );
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'valid_from' => [
                    'ObdobĂ­ spoluprĂˇce se pĹ™ekrĂ˝vĂˇ s jiĹľ uloĹľenĂ˝m obdobĂ­m tohoto Ĺ™idiÄŤe.',
                ],
            ]);
        }
    }

    private function assertMasterOrganization(
        int $organizationId,
    ): void {
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
                'Driver assignment administration requires master organization context.',
            );
        }
    }

    private function actor(
        Request $request,
    ): User {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        return $actor;
    }

    /**
     * @return array<string, int|string|null>
     */
    private function resource(
        DriverOrganizationAssignment $assignment,
    ): array {
        $today = CarbonImmutable::today()
            ->toDateString();

        $validFrom = CarbonImmutable::parse(
            $assignment->valid_from,
        )->toDateString();

        $validUntil = $assignment->valid_until === null
            ? null
            : CarbonImmutable::parse(
                $assignment->valid_until,
            )->toDateString();

        $status = 'active';

        if ($validFrom > $today) {
            $status = 'scheduled';
        } elseif (
            $validUntil !== null
            && $validUntil < $today
        ) {
            $status = 'ended';
        }

        return [
            'id' => (int) $assignment->getKey(),
            'organization_id' => (int) $assignment->getAttribute('organization_id'),
            'organization_name' => (string) $assignment->organization->getAttribute('name'),
            'organization_type' => (string) $assignment->organization->getAttribute('type'),
            'employment_type' => $assignment->getAttribute('employment_type'),
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'end_reason' => $assignment->getAttribute('end_reason'),
            'status' => $status,
        ];
    }

    private static function nullableTrimmed(
        mixed $value,
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }
}
