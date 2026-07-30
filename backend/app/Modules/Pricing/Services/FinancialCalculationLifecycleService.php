<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Models\FinancialCalculationEvent;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\PermissionRegistrar;

final class FinancialCalculationLifecycleService
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly FinancialCalculationWorkflow $workflow,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    public function startReview(
        int $financialCalculationId,
        int $reviewedByUserId,
        DateTimeInterface $reviewedAt,
        ?string $reason = null,
    ): FinancialCalculation {
        return $this->transition(
            financialCalculationId: $financialCalculationId,
            actedByUserId: $reviewedByUserId,
            transitionedAt: $reviewedAt,
            toStatus: FinancialCalculation::STATUS_UNDER_REVIEW,
            eventType: FinancialCalculationEvent::TYPE_REVIEW_STARTED,
            reason: $reason,
            reasonLabel: 'Review start reason',
        );
    }

    public function approve(
        int $financialCalculationId,
        int $approvedByUserId,
        DateTimeInterface $approvedAt,
        ?string $reason = null,
    ): FinancialCalculation {
        return $this->transition(
            financialCalculationId: $financialCalculationId,
            actedByUserId: $approvedByUserId,
            transitionedAt: $approvedAt,
            toStatus: FinancialCalculation::STATUS_APPROVED,
            eventType: FinancialCalculationEvent::TYPE_APPROVED,
            reason: $reason,
            reasonLabel: 'Approval reason',
        );
    }

    public function close(
        int $financialCalculationId,
        int $closedByUserId,
        DateTimeInterface $closedAt,
        ?string $reason = null,
    ): FinancialCalculation {
        return $this->transition(
            financialCalculationId: $financialCalculationId,
            actedByUserId: $closedByUserId,
            transitionedAt: $closedAt,
            toStatus: FinancialCalculation::STATUS_CLOSED,
            eventType: FinancialCalculationEvent::TYPE_CLOSED,
            reason: $reason,
            reasonLabel: 'Closing reason',
        );
    }

    public function cancel(
        int $financialCalculationId,
        int $cancelledByUserId,
        DateTimeInterface $cancelledAt,
        ?string $reason = null,
    ): FinancialCalculation {
        return $this->transition(
            financialCalculationId: $financialCalculationId,
            actedByUserId: $cancelledByUserId,
            transitionedAt: $cancelledAt,
            toStatus: FinancialCalculation::STATUS_CANCELLED,
            eventType: FinancialCalculationEvent::TYPE_CANCELLED,
            reason: $reason,
            reasonLabel: 'Cancellation reason',
        );
    }

    private function transition(
        int $financialCalculationId,
        int $actedByUserId,
        DateTimeInterface $transitionedAt,
        string $toStatus,
        string $eventType,
        ?string $reason,
        string $reasonLabel,
    ): FinancialCalculation {
        $organizationId =
            $this->organizationContext->requireId();

        $this->assertPositiveIdentifier(
            $financialCalculationId,
            'Financial calculation identifier',
        );

        $this->assertPositiveIdentifier(
            $actedByUserId,
            'Acting user identifier',
        );

        $transitionMoment =
            CarbonImmutable::instance($transitionedAt);

        $normalizedReason =
            $this->normalizeNullableText(
                $reason,
                $reasonLabel,
            );

        return DB::transaction(
            function () use (
                $organizationId,
                $financialCalculationId,
                $actedByUserId,
                $transitionMoment,
                $toStatus,
                $eventType,
                $normalizedReason,
            ): FinancialCalculation {
                $this->assertActiveOrganization(
                    $organizationId,
                );

                $this->assertActiveUserMembership(
                    $actedByUserId,
                    $organizationId,
                );

                $this->assertOrganizationPermission(
                    $actedByUserId,
                    $organizationId,
                    'compensation.manage',
                );

                $calculation =
                    FinancialCalculation::query()
                        ->whereKey($financialCalculationId)
                        ->where(
                            'organization_id',
                            $organizationId,
                        )
                        ->lockForUpdate()
                        ->first();

                if (
                    ! $calculation instanceof FinancialCalculation
                ) {
                    throw new DomainException(
                        (
                            'The financial calculation does not exist '.
                            'in the verified organization.'
                        ),
                    );
                }

                $currentStatus =
                    $calculation->getAttribute('status');

                if (! is_string($currentStatus)) {
                    throw new LogicException(
                        (
                            'The financial calculation status '.
                            'is not available.'
                        ),
                    );
                }

                $this->workflow->assertCanTransition(
                    $currentStatus,
                    $toStatus,
                );

                $calculationVersion =
                    $this->positiveInteger(
                        $calculation->getAttribute(
                            'calculation_version',
                        ),
                        'Financial calculation version',
                    );

                $calculation->setAttribute(
                    'status',
                    $toStatus,
                );

                if (
                    $toStatus ===
                    FinancialCalculation::STATUS_APPROVED
                ) {
                    $calculation->setAttribute(
                        'approved_by_user_id',
                        $actedByUserId,
                    );

                    $calculation->setAttribute(
                        'approved_at',
                        $transitionMoment,
                    );
                }

                if (
                    $toStatus ===
                    FinancialCalculation::STATUS_CLOSED
                ) {
                    $calculation->setAttribute(
                        'closed_at',
                        $transitionMoment,
                    );
                }

                $calculation->saveOrFail();

                $calculation->events()->create([
                    'organization_id' => $organizationId,

                    'event_type' => $eventType,

                    'from_status' => $currentStatus,

                    'to_status' => $toStatus,

                    'acted_by_user_id' => $actedByUserId,

                    'reason' => $normalizedReason,

                    'metadata' => $this->eventMetadata(
                        $eventType,
                        $calculationVersion,
                        $actedByUserId,
                    ),

                    'created_at' => $transitionMoment,
                ]);

                return $calculation->fresh([
                    'events',
                ]) ?? throw new LogicException(
                    (
                        'The transitioned financial calculation '.
                        'could not be reloaded.'
                    ),
                );
            },
            3,
        );
    }

    /**
     * @return array<string, int>
     */
    private function eventMetadata(
        string $eventType,
        int $calculationVersion,
        int $actedByUserId,
    ): array {
        $actorKey = match ($eventType) {
            FinancialCalculationEvent::TYPE_REVIEW_STARTED => 'reviewed_by_user_id',

            FinancialCalculationEvent::TYPE_APPROVED => 'approved_by_user_id',

            FinancialCalculationEvent::TYPE_CLOSED => 'closed_by_user_id',

            FinancialCalculationEvent::TYPE_CANCELLED => 'cancelled_by_user_id',

            default => throw new LogicException(
                'The financial calculation event type is not supported.',
            ),
        };

        return [
            'calculation_version' => $calculationVersion,
            $actorKey => $actedByUserId,
        ];
    }

    private function assertPositiveIdentifier(
        int $identifier,
        string $label,
    ): void {
        if ($identifier < 1) {
            throw new InvalidArgumentException(
                $label.' must be a positive integer.',
            );
        }
    }

    private function positiveInteger(
        mixed $value,
        string $label,
    ): int {
        if (
            ! is_int($value)
            && ! (
                is_string($value)
                && preg_match(
                    '/^[1-9][0-9]*$/D',
                    $value,
                ) === 1
            )
        ) {
            throw new LogicException(
                $label.' is not available.',
            );
        }

        $integer = (int) $value;

        if ($integer < 1) {
            throw new LogicException(
                $label.' must be positive.',
            );
        }

        return $integer;
    }

    private function normalizeNullableText(
        ?string $value,
        string $label,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        if ($normalized === '') {
            return null;
        }

        if (mb_strlen($normalized) > 2000) {
            throw new InvalidArgumentException(
                $label.' must not exceed 2000 characters.',
            );
        }

        return $normalized;
    }

    private function assertActiveOrganization(
        int $organizationId,
    ): void {
        $exists = Organization::query()
            ->whereKey($organizationId)
            ->where(
                'status',
                Organization::STATUS_ACTIVE,
            )
            ->exists();

        if (! $exists) {
            throw new DomainException(
                'The verified organization is not active.',
            );
        }
    }

    private function assertActiveUserMembership(
        int $userId,
        int $organizationId,
    ): void {
        $activeUserExists = User::query()
            ->whereKey($userId)
            ->where(
                'status',
                User::STATUS_ACTIVE,
            )
            ->exists();

        if (! $activeUserExists) {
            throw new DomainException(
                'The acting user is not active.',
            );
        }

        $memberships =
            OrganizationMembership::query()
                ->where(
                    'organization_id',
                    $organizationId,
                )
                ->where(
                    'user_id',
                    $userId,
                )
                ->where(
                    'status',
                    OrganizationMembership::STATUS_ACTIVE,
                )
                ->get();

        $moment = now();

        $membershipExists = $memberships->contains(
            static fn (
                OrganizationMembership $membership,
            ): bool => $membership->isActiveAt($moment),
        );

        if (! $membershipExists) {
            throw new DomainException(
                (
                    'The acting user does not have an active '.
                    'membership in the verified organization.'
                ),
            );
        }
    }

    private function assertOrganizationPermission(
        int $userId,
        int $organizationId,
        string $permission,
    ): void {
        $previousOrganizationId =
            $this->permissionRegistrar
                ->getPermissionsTeamId();

        try {
            $this->permissionRegistrar
                ->setPermissionsTeamId(
                    $organizationId,
                );

            $user = User::query()->find($userId);

            if (! $user instanceof User) {
                throw new DomainException(
                    'The acting user does not exist.',
                );
            }

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');

            try {
                $hasPermission =
                    $user->hasPermissionTo(
                        $permission,
                    );
            } catch (PermissionDoesNotExist) {
                $hasPermission = false;
            }

            if (! $hasPermission) {
                throw new DomainException(
                    sprintf(
                        (
                            'The acting user does not have '.
                            'the required organization permission: %s.'
                        ),
                        $permission,
                    ),
                );
            }
        } finally {
            $this->permissionRegistrar
                ->setPermissionsTeamId(
                    $previousOrganizationId,
                );
        }
    }
}
