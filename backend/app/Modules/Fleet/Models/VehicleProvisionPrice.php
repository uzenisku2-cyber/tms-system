<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

class VehicleProvisionPrice extends Model
{
    protected $fillable = ['public_id', 'price_uid', 'vehicle_provision_agreement_id', 'valid_from', 'valid_until', 'amount', 'currency', 'billing_period', 'billing_mode', 'vat_mode', 'vat_rate_basis_points', 'recorded_by_user_id', 'revision', 'notes'];

    protected $casts = ['valid_from' => 'date', 'valid_until' => 'date', 'amount' => 'decimal:2', 'vat_rate_basis_points' => 'integer', 'revision' => 'integer'];

    protected static function booted(): void
    {
        static::creating(static function (self $record): void {
            $record->public_id ??= (string) Str::uuid();
            $record->price_uid ??= (string) Str::uuid();
        });
        static::updating(static fn (): never => throw new LogicException('Vehicle provision prices are append-only.'));
        static::deleting(static fn (): never => throw new LogicException('Vehicle provision prices are append-only.'));
    }

    /** @return BelongsTo<VehicleProvisionAgreement, $this> */
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(VehicleProvisionAgreement::class, 'vehicle_provision_agreement_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
