<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

class VehicleProvisionAgreement extends Model
{
    protected $fillable = ['public_id', 'agreement_uid', 'vehicle_id', 'organization_context_id', 'provider_type', 'provider_organization_id', 'provider_user_id', 'recipient_type', 'recipient_organization_id', 'recipient_user_id', 'provision_mode', 'agreement_number', 'valid_from', 'valid_until', 'status', 'recorded_by_user_id', 'revision', 'notes'];

    protected $casts = ['valid_from' => 'date', 'valid_until' => 'date', 'revision' => 'integer'];

    protected static function booted(): void
    {
        static::creating(static function (self $record): void {
            $record->public_id ??= (string) Str::uuid();
            $record->agreement_uid ??= (string) Str::uuid();
        });
        static::updating(static fn (): never => throw new LogicException('Vehicle provision agreements are append-only.'));
        static::deleting(static fn (): never => throw new LogicException('Vehicle provision agreements are append-only.'));
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

    /** @return BelongsTo<Organization, $this> */
    public function providerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'provider_organization_id');
    }

    /** @return BelongsTo<User, $this> */
    public function providerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_user_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function recipientOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'recipient_organization_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /** @return HasMany<VehicleProvisionPrice, $this> */
    public function prices(): HasMany
    {
        return $this->hasMany(VehicleProvisionPrice::class);
    }
}
