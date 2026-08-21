<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DriverQualityProfile extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_ARCHIVED,
    ];

    protected $table = 'driver_quality_profiles';

    /** @var list<string> */
    protected $fillable = [
        'public_id',
        'organization_id',
        'code',
        'name',
        'description',
        'status',
        'current_version',
        'created_by_user_id',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
        'current_version' => 1,
    ];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id',
        );
    }

    /** @return HasMany<DriverQualityProfileVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(
            DriverQualityProfileVersion::class,
        )->orderBy('version_number');
    }

    /** @return HasMany<DriverQualityProfileBinding, $this> */
    public function bindings(): HasMany
    {
        return $this->hasMany(
            DriverQualityProfileBinding::class,
        )->orderBy('valid_from');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'organization_id' => 'integer',
            'current_version' => 'integer',
            'created_by_user_id' => 'integer',
        ];
    }
}
