<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

class VehicleInstallment extends Model
{
    protected $fillable = ['public_id', 'installment_uid', 'vehicle_installment_schedule_id', 'sequence_number', 'due_on', 'principal_amount', 'finance_charge_amount', 'other_amount', 'total_amount', 'currency', 'status', 'recorded_by_user_id', 'revision', 'notes'];

    protected $casts = ['sequence_number' => 'integer', 'due_on' => 'date', 'principal_amount' => 'decimal:2', 'finance_charge_amount' => 'decimal:2', 'other_amount' => 'decimal:2', 'total_amount' => 'decimal:2', 'revision' => 'integer'];

    protected static function booted(): void
    {
        static::creating(static function (self $record): void {
            $record->public_id ??= (string) Str::uuid();
            $record->installment_uid ??= (string) Str::uuid();
        });
        static::updating(static fn (): never => throw new LogicException('Vehicle installments are append-only.'));
        static::deleting(static fn (): never => throw new LogicException('Vehicle installments are append-only.'));
    }

    /** @return BelongsTo<VehicleInstallmentSchedule, $this> */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(VehicleInstallmentSchedule::class, 'vehicle_installment_schedule_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
