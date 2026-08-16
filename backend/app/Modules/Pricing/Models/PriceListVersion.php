<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceListVersion extends Model
{
    public const UPDATED_AT = null;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REPLACED = 'replaced';

    public const STATUS_EXPIRED = 'expired';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_APPROVED,
        self::STATUS_ACTIVE,
        self::STATUS_REPLACED,
        self::STATUS_EXPIRED,
    ];

    protected $table = 'price_list_versions';

    /** @var list<string> */
    protected $fillable = [
        'price_list_id',
        'version_number',
        'lock_version',
        'status',
        'valid_from',
        'valid_until',
        'change_reason',
        'created_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'activated_at',
        'created_at',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'lock_version' => 1,
    ];

    /** @return BelongsTo<PriceList, $this> */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(
            PriceList::class,
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
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by_user_id',
        );
    }

    /** @return HasMany<PriceListItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(
            PriceListItem::class,
        )->orderBy('position');
    }

    /**
     * @return HasMany<PriceListConditionalRule, $this>
     */
    public function conditionalRules(): HasMany
    {
        return $this->hasMany(
            PriceListConditionalRule::class,
        )->orderBy('position');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isApplicableOn(
        DateTimeInterface $serviceDate,
    ): bool {
        if (
            (
                ! $this->isActive()
                && ! $this->isReplaced()
                && ! $this->isExpired()
            )
            || $this->valid_from === null
            || (
                (
                    $this->isReplaced()
                    || $this->isExpired()
                )
                && $this->valid_until === null
            )
        ) {
            return false;
        }

        $date = CarbonImmutable::instance(
            $serviceDate,
        )->startOfDay();

        $validFrom = CarbonImmutable::parse(
            (string) $this->valid_from,
        )->startOfDay();

        if ($validFrom->isAfter($date)) {
            return false;
        }

        if ($this->valid_until === null) {
            return true;
        }

        $validUntil = CarbonImmutable::parse(
            (string) $this->valid_until,
        )->startOfDay();

        return ! $validUntil->isBefore($date);
    }

    public function isReplaced(): bool
    {
        return $this->status === self::STATUS_REPLACED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'lock_version' => 'integer',
            'valid_from' => 'immutable_date',
            'valid_until' => 'immutable_date',
            'approved_at' => 'immutable_datetime',
            'activated_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
