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

class VehicleFinancingAgreement extends Model
{
    protected $fillable = ['public_id', 'financing_uid', 'vehicle_id', 'organization_context_id', 'financing_type', 'financier_organization_id', 'external_financier_name', 'debtor_type', 'debtor_organization_id', 'debtor_user_id', 'agreement_number', 'effective_from', 'effective_until', 'currency', 'total_amount', 'initial_payment_amount', 'residual_value_amount', 'status', 'recorded_by_user_id', 'revision', 'notes'];

    protected $casts = ['effective_from' => 'date', 'effective_until' => 'date', 'total_amount' => 'decimal:2', 'initial_payment_amount' => 'decimal:2', 'residual_value_amount' => 'decimal:2', 'revision' => 'integer'];

    protected static function booted(): void
    {
        static::creating(static function (self $record): void {
            $record->public_id ??= (string) Str::uuid();
            $record->financing_uid ??= (string) Str::uuid();
        });
        static::updating(static fn (): never => throw new LogicException('Vehicle financing agreements are append-only.'));
        static::deleting(static fn (): never => throw new LogicException('Vehicle financing agreements are append-only.'));
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
    public function financierOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'financier_organization_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function debtorOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'debtor_organization_id');
    }

    /** @return BelongsTo<User, $this> */
    public function debtorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'debtor_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /** @return HasMany<VehicleInstallmentSchedule, $this> */
    public function schedules(): HasMany
    {
        return $this->hasMany(VehicleInstallmentSchedule::class);
    }
}
