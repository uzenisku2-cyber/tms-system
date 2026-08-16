<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DriverPriceList extends Model
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

    protected $table = 'driver_price_lists';

    /** @var list<string> */
    protected $fillable = [
        'public_id',
        'driver_organization_assignment_id',
        'managed_by_organization_id',
        'code',
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

    /** @return BelongsTo<DriverOrganizationAssignment, $this> */
    public function driverOrganizationAssignment(): BelongsTo
    {
        return $this->belongsTo(
            DriverOrganizationAssignment::class,
        );
    }

    /** @return BelongsTo<Organization, $this> */
    public function managedByOrganization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class,
            'managed_by_organization_id',
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

    /** @return HasMany<DriverPriceListVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(
            DriverPriceListVersion::class,
        )->orderBy('version_number');
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
