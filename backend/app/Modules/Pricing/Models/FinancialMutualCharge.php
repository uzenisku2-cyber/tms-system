<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FinancialMutualCharge extends Model
{
    use HasUuids;

    public const PARTY_ORGANIZATION = 'organization';

    public const PARTY_DRIVER = 'driver';

    public const DIRECTION_RECEIVABLE = 'receivable';

    public const DIRECTION_PAYABLE = 'payable';

    public const CATEGORY_FUEL = 'fuel';

    public const CATEGORY_VEHICLE_COST = 'vehicle_cost';

    public const CATEGORY_DAMAGE = 'damage';

    public const CATEGORY_ADVANCE = 'advance';

    public const CATEGORY_RENTAL = 'rental';

    public const CATEGORY_PENALTY = 'penalty';

    public const CATEGORY_BONUS = 'bonus';

    public const CATEGORY_OTHER = 'other';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_DISPUTED = 'disputed';

    public const STATUS_REVERSED = 'reversed';

    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_SHARED = 'shared';

    /** @var list<string> */
    public const PARTY_TYPES = [self::PARTY_ORGANIZATION, self::PARTY_DRIVER];

    /** @var list<string> */
    public const DIRECTIONS = [self::DIRECTION_RECEIVABLE, self::DIRECTION_PAYABLE];

    /** @var list<string> */
    public const CATEGORIES = [
        self::CATEGORY_FUEL,
        self::CATEGORY_VEHICLE_COST,
        self::CATEGORY_DAMAGE,
        self::CATEGORY_ADVANCE,
        self::CATEGORY_RENTAL,
        self::CATEGORY_PENALTY,
        self::CATEGORY_BONUS,
        self::CATEGORY_OTHER,
    ];

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_CONFIRMED,
        self::STATUS_DISPUTED,
        self::STATUS_REVERSED,
    ];

    protected $fillable = [
        'public_id',
        'owner_organization_id',
        'counterparty_organization_id',
        'counterparty_driver_id',
        'counterparty_type',
        'direction',
        'category',
        'description',
        'service_period_from',
        'service_period_until',
        'amount_minor',
        'currency',
        'vat_treatment',
        'offset_eligible',
        'status',
        'visibility_status',
        'source_type',
        'source_public_id',
        'source_snapshot',
        'idempotency_key',
        'command_fingerprint',
        'revision',
        'created_by_user_id',
        'confirmed_by_user_id',
        'confirmed_at',
        'shared_at',
        'reversed_at',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function ownerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'owner_organization_id');
    }

    public function counterpartyOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'counterparty_organization_id');
    }

    public function counterpartyDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'counterparty_driver_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(FinancialMutualChargeEvent::class)->orderBy('revision');
    }

    protected function casts(): array
    {
        return [
            'service_period_from' => 'immutable_date',
            'service_period_until' => 'immutable_date',
            'amount_minor' => 'integer',
            'offset_eligible' => 'boolean',
            'source_snapshot' => 'array',
            'revision' => 'integer',
            'confirmed_at' => 'immutable_datetime',
            'shared_at' => 'immutable_datetime',
            'reversed_at' => 'immutable_datetime',
        ];
    }
}
