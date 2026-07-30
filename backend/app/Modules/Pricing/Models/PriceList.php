<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceList extends Model
{
    use HasUuids;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_ARCHIVED,
    ];

    protected $table = 'price_lists';

    /** @var list<string> */
    protected $fillable = [
        'public_id',
        'organization_relationship_id',
        'owner_organization_id',
        'customer_organization_id',
        'provider_organization_id',
        'name',
        'description',
        'currency',
        'status',
        'current_version',
        'created_by_user_id',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'current_version' => 1,
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<OrganizationRelationship, $this>
     */
    public function organizationRelationship(): BelongsTo
    {
        return $this->belongsTo(
            OrganizationRelationship::class,
        );
    }

    /** @return BelongsTo<Organization, $this> */
    public function ownerOrganization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class,
            'owner_organization_id',
        );
    }

    /** @return BelongsTo<Organization, $this> */
    public function customerOrganization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class,
            'customer_organization_id',
        );
    }

    /** @return BelongsTo<Organization, $this> */
    public function providerOrganization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class,
            'provider_organization_id',
        );
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id',
        );
    }

    /** @return HasMany<PriceListVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(
            PriceListVersion::class,
        );
    }

    /**
     * @param  Builder<PriceList>  $query
     * @return Builder<PriceList>
     */
    public function scopeForOwnerOrganization(
        Builder $query,
        int $organizationId,
    ): Builder {
        return $query->where(
            'owner_organization_id',
            $organizationId,
        );
    }

    /**
     * @param  Builder<PriceList>  $query
     * @return Builder<PriceList>
     */
    public function scopeForParticipatingOrganization(
        Builder $query,
        int $organizationId,
    ): Builder {
        return $query->where(
            static function (Builder $partyQuery) use (
                $organizationId,
            ): void {
                $partyQuery
                    ->where(
                        'customer_organization_id',
                        $organizationId,
                    )
                    ->orWhere(
                        'provider_organization_id',
                        $organizationId,
                    );
            },
        );
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_version' => 'integer',
        ];
    }
}
