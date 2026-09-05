<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

class VehicleInsurancePolicy extends Model
{
    protected $fillable = ['public_id', 'record_uid', 'vehicle_id', 'organization_context_id', 'policy_type', 'insurer_name', 'policy_number', 'valid_from', 'valid_until', 'status', 'coverage_amount', 'deductible_amount', 'currency', 'primary_document_id', 'recorded_by_user_id', 'revision', 'notes'];

    protected $casts = ['valid_from' => 'date', 'valid_until' => 'date', 'coverage_amount' => 'decimal:2', 'deductible_amount' => 'decimal:2', 'revision' => 'integer'];

    protected static function booted(): void
    {
        static::creating(static function (self $record): void {
            $record->public_id ??= (string) Str::uuid();
            $record->record_uid ??= (string) Str::uuid();
        });
        static::updating(static fn (): never => throw new LogicException('Vehicle insurance policies are append-only.'));
        static::deleting(static fn (): never => throw new LogicException('Vehicle insurance policies are append-only.'));
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
