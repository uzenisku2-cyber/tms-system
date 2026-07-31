<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

final class PriceListWriteService
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function createDraft(
        User $actor,
        array $input,
    ): PriceList {
        $organizationId =
            $this->organizationContext->requireId();

        $actorId = (int) $actor->getKey();

        if ($actorId < 1) {
            throw new LogicException(
                'The authenticated user has no valid identifier.',
            );
        }

        $relationshipId = $this->positiveInteger(
            $input,
            'organization_relationship_id',
        );

        $name = $this->requiredString(
            $input,
            'name',
        );

        $description = $this->nullableString(
            $input,
            'description',
        );

        $currency = $this->requiredString(
            $input,
            'currency',
        );

        $validFrom = $this->nullableString(
            $input,
            'valid_from',
        );

        $validUntil = $this->nullableString(
            $input,
            'valid_until',
        );

        $changeReason = $this->nullableString(
            $input,
            'change_reason',
        );

        return DB::transaction(
            function () use (
                $organizationId,
                $actorId,
                $relationshipId,
                $name,
                $description,
                $currency,
                $validFrom,
                $validUntil,
                $changeReason,
            ): PriceList {
                $relationship =
                    OrganizationRelationship::query()
                        ->with([
                            'sourceOrganization',
                            'targetOrganization',
                        ])
                        ->whereKey($relationshipId)
                        ->where(
                            'source_organization_id',
                            $organizationId,
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    $relationship->getAttribute(
                        'relationship_type',
                    ) !==
                    OrganizationRelationship::TYPE_SUBCONTRACTING
                ) {
                    $this->validationFailure(
                        'organization_relationship_id',
                        (
                            'The selected relationship does not support '
                            .'price-list management.'
                        ),
                    );
                }

                if (! $relationship->isActiveAt(now())) {
                    $this->validationFailure(
                        'organization_relationship_id',
                        (
                            'The selected commercial relationship is not '
                            .'currently active.'
                        ),
                    );
                }

                $customer =
                    $relationship->sourceOrganization;

                $provider =
                    $relationship->targetOrganization;

                if (
                    ! $customer instanceof Organization
                    || ! $provider instanceof Organization
                ) {
                    throw new LogicException(
                        (
                            'The selected commercial relationship has '
                            .'incomplete organization data.'
                        ),
                    );
                }

                if (
                    ! $customer->isActive()
                    || ! $provider->isActive()
                ) {
                    $this->validationFailure(
                        'organization_relationship_id',
                        (
                            'Both organizations in the selected relationship '
                            .'must be active.'
                        ),
                    );
                }

                $customerId =
                    (int) $customer->getKey();

                $providerId =
                    (int) $provider->getKey();

                if (
                    $customerId < 1
                    || $providerId < 1
                    || $customerId === $providerId
                ) {
                    $this->validationFailure(
                        'organization_relationship_id',
                        (
                            'The selected relationship does not contain '
                            .'two distinct organizations.'
                        ),
                    );
                }

                if ($customerId !== $organizationId) {
                    throw new LogicException(
                        (
                            'The verified organization does not match '
                            .'the relationship customer.'
                        ),
                    );
                }

                $priceList = PriceList::query()->create([
                    'organization_relationship_id' => $relationship->getKey(),

                    'owner_organization_id' => $customerId,

                    'customer_organization_id' => $customerId,

                    'provider_organization_id' => $providerId,

                    'name' => $name,

                    'description' => $description,

                    'currency' => $currency,

                    'status' => PriceList::STATUS_DRAFT,

                    'current_version' => 1,

                    'created_by_user_id' => $actorId,
                ]);

                $priceList->versions()->create([
                    'version_number' => 1,

                    'status' => PriceListVersion::STATUS_DRAFT,

                    'valid_from' => $validFrom,

                    'valid_until' => $validUntil,

                    'change_reason' => $changeReason,

                    'created_by_user_id' => $actorId,
                ]);

                return $priceList->refresh();
            },
            3,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function positiveInteger(
        array $input,
        string $key,
    ): int {
        $value = $input[$key] ?? null;

        if (
            is_int($value)
            && $value > 0
        ) {
            return $value;
        }

        if (
            is_string($value)
            && ctype_digit($value)
            && (int) $value > 0
        ) {
            return (int) $value;
        }

        throw new LogicException(
            sprintf(
                'Validated field [%s] must be a positive integer.',
                $key,
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function requiredString(
        array $input,
        string $key,
    ): string {
        $value = $input[$key] ?? null;

        if (
            ! is_string($value)
            || $value === ''
        ) {
            throw new LogicException(
                sprintf(
                    'Validated field [%s] must be a non-empty string.',
                    $key,
                ),
            );
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function nullableString(
        array $input,
        string $key,
    ): ?string {
        $value = $input[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new LogicException(
                sprintf(
                    'Validated field [%s] must be a string or null.',
                    $key,
                ),
            );
        }

        return $value;
    }

    private function validationFailure(
        string $field,
        string $message,
    ): never {
        throw ValidationException::withMessages([
            $field => [
                $message,
            ],
        ]);
    }
}
