<?php

declare(strict_types=1);

namespace App\Modules\Organizations\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $user_id
 * @property string $relationship_type
 * @property string $status
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read User $user
 */
class OrganizationMembership extends Model
{
    public const RELATIONSHIP_OWNER = 'owner';

    public const RELATIONSHIP_EMPLOYEE = 'employee';

    public const RELATIONSHIP_CONTRACTOR = 'contractor';

    public const RELATIONSHIP_REPRESENTATIVE = 'representative';

    public const STATUS_INVITED = 'invited';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_ENDED = 'ended';

    /**
     * @var list<string>
     */
    public const RELATIONSHIP_TYPES = [
        self::RELATIONSHIP_OWNER,
        self::RELATIONSHIP_EMPLOYEE,
        self::RELATIONSHIP_CONTRACTOR,
        self::RELATIONSHIP_REPRESENTATIVE,
    ];

    /**
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_INVITED,
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
        self::STATUS_ENDED,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'user_id',
        'relationship_type',
        'status',
        'valid_from',
        'valid_until',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_INVITED,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActiveAt(?Carbon $moment = null): bool
    {
        $moment ??= now();

        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->valid_from !== null && $this->valid_from->isAfter($moment)) {
            return false;
        }

        if ($this->valid_until !== null && $this->valid_until->isBefore($moment)) {
            return false;
        }

        return true;
    }
}
