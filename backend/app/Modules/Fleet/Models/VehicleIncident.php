<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

class VehicleIncident extends Model
{
    protected $fillable = ['public_id', 'record_uid', 'vehicle_id', 'organization_context_id', 'incident_type', 'occurred_at', 'reported_at', 'resolved_at', 'status', 'severity', 'driver_user_id', 'responsible_organization_id', 'location', 'police_reference', 'insurance_claim_reference', 'primary_document_id', 'description', 'recorded_by_user_id', 'revision'];

    protected $casts = ['occurred_at' => 'datetime', 'reported_at' => 'datetime', 'resolved_at' => 'datetime', 'revision' => 'integer'];

    protected static function booted(): void
    {
        static::creating(static function (self $record): void {
            $record->public_id ??= (string) Str::uuid();
            $record->record_uid ??= (string) Str::uuid();
        });
        static::updating(static fn (): never => throw new LogicException('Vehicle incidents are append-only.'));
        static::deleting(static fn (): never => throw new LogicException('Vehicle incidents are append-only.'));
    }

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
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function responsibleOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'responsible_organization_id');
    }

    /** @return BelongsTo<VehicleDocument, $this> */
    public function primaryDocument(): BelongsTo
    {
        return $this->belongsTo(VehicleDocument::class, 'primary_document_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
