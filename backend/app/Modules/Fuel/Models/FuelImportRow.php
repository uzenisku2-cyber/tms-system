<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class FuelImportRow extends Model
{
    protected $fillable = ['fuel_import_batch_id', 'source_row', 'status', 'row_fingerprint', 'provider_transaction_identifier', 'raw_payload', 'normalized_payload', 'validation_messages', 'fuel_transaction_id', 'duplicate_fuel_transaction_id'];

    protected $casts = ['source_row' => 'integer', 'raw_payload' => 'array', 'normalized_payload' => 'array', 'validation_messages' => 'array'];

    /** @return HasMany<FuelImportRowCorrection, $this> */
    public function corrections(): HasMany
    {
        return $this->hasMany(FuelImportRowCorrection::class)->orderBy('revision');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(FuelImportBatch::class, 'fuel_import_batch_id');
    }

    public function finalization(): HasOne
    {
        return $this->hasOne(FuelImportRowFinalization::class, 'fuel_import_row_id');
    }
}
