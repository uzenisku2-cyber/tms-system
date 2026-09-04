<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleResponsibility extends Model
{
    protected $fillable = ['public_id', 'vehicle_id', 'organization_context_id', 'responsibility_type', 'party_type', 'party_organization_id', 'party_user_id', 'external_party_name', 'valid_from', 'valid_until', 'source', 'status', 'recorded_by_user_id', 'reason', 'revision'];

    protected $casts = ['valid_from' => 'datetime', 'valid_until' => 'datetime', 'revision' => 'integer'];

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
    public function partyOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'party_organization_id');
    }

    /** @return BelongsTo<User, $this> */
    public function partyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'party_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
