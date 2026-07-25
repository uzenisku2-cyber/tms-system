<?php

declare(strict_types=1);

namespace App\Modules\Organizations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $source_organization_id
 * @property int $target_organization_id
 * @property string $relationship_type
 * @property string $status
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $sourceOrganization
 * @property-read Organization $targetOrganization
 */
class OrganizationRelationship extends Model
{
    public const TYPE_SUBCONTRACTING = 'subcontracting';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_ENDED = 'ended';

    /**
     * @var list<string>
     */
    public const TYPES = [
        self::TYPE_SUBCONTRACTING,
    ];

    /**
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
        self::STATUS_ENDED,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'source_organization_id',
        'target_organization_id',
        'relationship_type',
        'status',
        'valid_from',
        'valid_until',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
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
    public function sourceOrganization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class,
            'source_organization_id'
        );
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function targetOrganization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class,
            'target_organization_id'
        );
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
