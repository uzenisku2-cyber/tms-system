<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

final class DriverSupervisoryScope extends Model
{
    public const TYPE_ORGANIZATION = 'organization';

    public const TYPE_DRIVER = 'driver';

    /**
     * @var list<string>
     */
    public const TYPES = [
        self::TYPE_ORGANIZATION,
        self::TYPE_DRIVER,
    ];

    protected $fillable = [
        'organization_id',
        'supervisor_user_id',
        'scope_type',
        'target_organization_id',
        'target_driver_id',
        'organization_relationship_id',
        'valid_from',
        'valid_until',
        'created_by_user_id',
        'ended_by_user_id',
        'end_reason',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class,
        );
    }

    /** @return BelongsTo<User, $this> */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'supervisor_user_id',
        );
    }

    /** @return BelongsTo<Organization, $this> */
    public function targetOrganization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class,
            'target_organization_id',
        );
    }

    /** @return BelongsTo<Driver, $this> */
    public function targetDriver(): BelongsTo
    {
        return $this->belongsTo(
            Driver::class,
            'target_driver_id',
        );
    }

    /** @return BelongsTo<OrganizationRelationship, $this> */
    public function organizationRelationship(): BelongsTo
    {
        return $this->belongsTo(
            OrganizationRelationship::class,
            'organization_relationship_id',
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
    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'ended_by_user_id',
        );
    }

    public function isActiveAt(
        Carbon $moment,
    ): bool {
        $date = $moment->toDateString();

        if (Carbon::parse($this->valid_from)->toDateString() > $date) {
            return false;
        }

        if (
            $this->valid_until !== null
            && Carbon::parse($this->valid_until)->toDateString() < $date
        ) {
            return false;
        }

        return true;
    }
}
