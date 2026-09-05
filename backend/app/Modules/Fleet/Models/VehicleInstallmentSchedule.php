<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

class VehicleInstallmentSchedule extends Model
{
    protected $fillable = ['public_id', 'schedule_uid', 'vehicle_financing_agreement_id', 'starts_on', 'ends_on', 'installment_count', 'planned_total_amount', 'currency', 'frequency', 'status', 'recorded_by_user_id', 'revision', 'notes'];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'installment_count' => 'integer', 'planned_total_amount' => 'decimal:2', 'revision' => 'integer'];

    protected static function booted(): void
    {
        static::creating(static function (self $record): void {
            $record->public_id ??= (string) Str::uuid();
            $record->schedule_uid ??= (string) Str::uuid();
        });
        static::updating(static fn (): never => throw new LogicException('Vehicle installment schedules are append-only.'));
        static::deleting(static fn (): never => throw new LogicException('Vehicle installment schedules are append-only.'));
    }

    /** @return BelongsTo<VehicleFinancingAgreement, $this> */
    public function financingAgreement(): BelongsTo
    {
        return $this->belongsTo(VehicleFinancingAgreement::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /** @return HasMany<VehicleInstallment, $this> */
    public function installments(): HasMany
    {
        return $this->hasMany(VehicleInstallment::class);
    }
}
