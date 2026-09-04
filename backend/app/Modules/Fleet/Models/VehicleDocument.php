<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleDocument extends Model
{
    protected $fillable = ['public_id', 'vehicle_id', 'organization_context_id', 'document_type', 'title', 'storage_reference', 'issue_date', 'valid_from', 'valid_until', 'verification_status', 'access_classification', 'uploaded_by_user_id', 'revision'];

    protected $casts = ['issue_date' => 'date', 'valid_from' => 'date', 'valid_until' => 'date', 'revision' => 'integer'];

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

    /** @return BelongsTo<User, $this> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
