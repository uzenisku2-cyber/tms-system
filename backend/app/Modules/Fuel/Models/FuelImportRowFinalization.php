<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FuelImportRowFinalization extends Model
{
    protected $fillable = ['public_id', 'fuel_import_row_id', 'fuel_import_row_correction_id', 'fuel_transaction_id', 'correction_revision', 'from_status', 'to_status', 'before_snapshot', 'after_snapshot', 'reason', 'finalized_by_user_id', 'finalized_at'];

    protected $casts = ['correction_revision' => 'integer', 'before_snapshot' => 'array', 'after_snapshot' => 'array', 'finalized_at' => 'datetime'];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function row(): BelongsTo
    {
        return $this->belongsTo(FuelImportRow::class, 'fuel_import_row_id');
    }

    public function correction(): BelongsTo
    {
        return $this->belongsTo(FuelImportRowCorrection::class, 'fuel_import_row_correction_id');
    }

    /** @return BelongsTo<FuelTransaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FuelTransaction::class, 'fuel_transaction_id');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by_user_id');
    }
}
