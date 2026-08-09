<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Models;

use App\Models\User;
use App\Modules\Trips\Models\Trip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $external_driver_id
 * @property string|null $phone
 * @property string|null $email
 * @property string $license_number
 * @property string|null $license_category
 * @property bool $active
 * @property string $status
 * @property Carbon|null $status_changed_at
 */
class Driver extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_INACTIVE = 'inactive';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
        self::STATUS_INACTIVE,
    ];

    /**
     * Czech driving-licence categories used by the pilot UI and API.
     *
     * @var list<string>
     */
    public const LICENSE_CATEGORIES = [
        'AM',
        'A1',
        'A2',
        'A',
        'B1',
        'B',
        'C1',
        'C',
        'D1',
        'D',
        'B+E',
        'C1+E',
        'C+E',
        'D1+E',
        'D+E',
        'T',
    ];

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'external_driver_id',
        'phone',
        'email',
        'license_number',
        'license_category',
        'active',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'active' => true,
        'status' => self::STATUS_ACTIVE,
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Trip, $this> */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class, 'driver_id');
    }

    /** @return HasMany<DriverOrganizationAssignment, $this> */
    public function organizationAssignments(): HasMany
    {
        return $this->hasMany(
            DriverOrganizationAssignment::class,
            'driver_id',
        );
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function canOperate(): bool
    {
        return $this->isActive();
    }

    public function hasActiveTrip(): bool
    {
        return $this->trips()
            ->whereIn('status', [
                Trip::STATUS_ASSIGNED,
                Trip::STATUS_STARTED,
            ])
            ->exists();
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'status_changed_at' => 'datetime',
        ];
    }
}
