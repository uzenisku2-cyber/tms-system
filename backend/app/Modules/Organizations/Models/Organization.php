<?php

declare(strict_types=1);

namespace App\Modules\Organizations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $type
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Organization extends Model
{
    public const TYPE_MASTER = 'master';

    public const TYPE_CARRIER = 'carrier';

    public const TYPE_SUBCONTRACTOR = 'subcontractor';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_ARCHIVED = 'archived';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_MASTER,
        self::TYPE_CARRIER,
        self::TYPE_SUBCONTRACTOR,
    ];

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
        self::STATUS_ARCHIVED,
    ];

    /** @var list<string> */
    protected $fillable = [
        'name',
        'type',
        'status',
        'registration_number',
        'vat_number',
        'street',
        'city',
        'postal_code',
        'country_code',
        'contact_email',
        'contact_phone',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    /** @return HasMany<OrganizationMembership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    /** @return HasMany<OrganizationRelationship, $this> */
    public function outgoingRelationships(): HasMany
    {
        return $this->hasMany(
            OrganizationRelationship::class,
            'source_organization_id'
        );
    }

    /** @return HasMany<OrganizationRelationship, $this> */
    public function incomingRelationships(): HasMany
    {
        return $this->hasMany(
            OrganizationRelationship::class,
            'target_organization_id'
        );
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
