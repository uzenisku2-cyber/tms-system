<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DriverQualityProfileVersion extends Model
{
    public const UPDATED_AT = null;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REPLACED = 'replaced';

    public const STATUS_EXPIRED = 'expired';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_REPLACED,
        self::STATUS_EXPIRED,
    ];

    public const METHOD_PROCESSED_SHARE = 'processed_share';

    public const METHOD_DISABLED = 'disabled';

    /** @var list<string> */
    public const CALCULATION_METHODS = [
        self::METHOD_PROCESSED_SHARE,
        self::METHOD_DISABLED,
    ];

    protected $table = 'driver_quality_profile_versions';

    /** @var list<string> */
    protected $fillable = [
        'driver_quality_profile_id',
        'version_number',
        'lock_version',
        'status',
        'calculation_method',
        'valid_from',
        'valid_until',
        'change_reason',
        'created_by_user_id',
        'activated_by_user_id',
        'activated_at',
        'created_at',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'calculation_method' => self::METHOD_PROCESSED_SHARE,
        'lock_version' => 1,
    ];

    /** @return BelongsTo<DriverQualityProfile, $this> */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(
            DriverQualityProfile::class,
            'driver_quality_profile_id',
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

    /** @return BelongsTo<User, $this> */
    public function activatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'activated_by_user_id',
        );
    }

    /** @return HasMany<DriverQualityProfileComponent, $this> */
    public function components(): HasMany
    {
        return $this->hasMany(
            DriverQualityProfileComponent::class,
        )->orderBy('position');
    }

    public function isApplicableOn(DateTimeInterface $serviceDate): bool
    {
        if (
            ! in_array(
                $this->status,
                [
                    self::STATUS_ACTIVE,
                    self::STATUS_REPLACED,
                    self::STATUS_EXPIRED,
                ],
                true,
            )
            || $this->valid_from === null
        ) {
            return false;
        }

        $date = CarbonImmutable::instance($serviceDate)->startOfDay();
        $validFrom = CarbonImmutable::parse(
            (string) $this->valid_from,
        )->startOfDay();

        if ($validFrom->isAfter($date)) {
            return false;
        }

        if ($this->valid_until === null) {
            return true;
        }

        return ! CarbonImmutable::parse(
            (string) $this->valid_until,
        )->startOfDay()->isBefore($date);
    }

    public function isDisabled(): bool
    {
        return $this->calculation_method === self::METHOD_DISABLED;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'driver_quality_profile_id' => 'integer',
            'version_number' => 'integer',
            'lock_version' => 'integer',
            'valid_from' => 'immutable_date',
            'valid_until' => 'immutable_date',
            'created_by_user_id' => 'integer',
            'activated_by_user_id' => 'integer',
            'activated_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
