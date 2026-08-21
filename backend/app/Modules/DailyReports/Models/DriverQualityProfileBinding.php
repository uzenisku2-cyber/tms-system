<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Models;

use App\Models\User;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DriverQualityProfileBinding extends Model
{
    public const SCOPE_ORGANIZATION = 'organization';

    public const SCOPE_CARRIER_RELATIONSHIP = 'carrier_relationship';

    public const SCOPE_DRIVER_ASSIGNMENT = 'driver_assignment';

    /** @var list<string> */
    public const SCOPE_TYPES = [
        self::SCOPE_ORGANIZATION,
        self::SCOPE_CARRIER_RELATIONSHIP,
        self::SCOPE_DRIVER_ASSIGNMENT,
    ];

    protected $table = 'driver_quality_profile_bindings';

    /** @var list<string> */
    protected $fillable = [
        'organization_id',
        'driver_quality_profile_id',
        'scope_type',
        'scope_key',
        'organization_relationship_id',
        'driver_organization_assignment_id',
        'valid_from',
        'valid_until',
        'created_by_user_id',
    ];

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<DriverQualityProfile, $this> */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(
            DriverQualityProfile::class,
            'driver_quality_profile_id',
        );
    }

    /** @return BelongsTo<OrganizationRelationship, $this> */
    public function carrierRelationship(): BelongsTo
    {
        return $this->belongsTo(
            OrganizationRelationship::class,
            'organization_relationship_id',
        );
    }

    /** @return BelongsTo<DriverOrganizationAssignment, $this> */
    public function driverAssignment(): BelongsTo
    {
        return $this->belongsTo(
            DriverOrganizationAssignment::class,
            'driver_organization_assignment_id',
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

    public static function organizationScopeKey(): string
    {
        return self::SCOPE_ORGANIZATION;
    }

    public static function carrierScopeKey(int $relationshipId): string
    {
        return 'relationship:'.$relationshipId;
    }

    public static function driverScopeKey(int $assignmentId): string
    {
        return 'assignment:'.$assignmentId;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'organization_id' => 'integer',
            'driver_quality_profile_id' => 'integer',
            'organization_relationship_id' => 'integer',
            'driver_organization_assignment_id' => 'integer',
            'valid_from' => 'immutable_date',
            'valid_until' => 'immutable_date',
            'created_by_user_id' => 'integer',
        ];
    }
}
