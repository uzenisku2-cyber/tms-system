<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/** @property int $id @property int $vehicle_id @property string $owner_type @property int|null $owner_organization_id @property int|null $owner_user_id @property string|null $external_owner_name @property int $ownership_share_basis_points @property Carbon $valid_from @property Carbon|null $valid_until @property string $verification_status @property int $revision */
class VehicleOwnership extends Model
{
    protected $fillable = ['public_id', 'vehicle_id', 'organization_context_id', 'owner_type', 'owner_organization_id', 'owner_user_id', 'external_owner_name', 'ownership_share_basis_points', 'valid_from', 'valid_until', 'acquisition_basis', 'verification_status', 'recorded_by_user_id', 'change_reason', 'revision'];

    protected $casts = ['ownership_share_basis_points' => 'integer', 'valid_from' => 'datetime', 'valid_until' => 'datetime', 'revision' => 'integer'];

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<Organization, $this> */
    public function organizationContext(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_context_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function ownerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'owner_organization_id');
    }

    /** @return BelongsTo<User, $this> */
    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
