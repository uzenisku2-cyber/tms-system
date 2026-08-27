<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FuelImportRowCorrection extends Model
{
    protected $fillable = ['public_id', 'fuel_import_row_id', 'revision', 'original_payload', 'corrected_payload', 'reason', 'corrected_by_user_id'];

    protected $casts = ['revision' => 'integer', 'original_payload' => 'array', 'corrected_payload' => 'array'];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function row(): BelongsTo
    {
        return $this->belongsTo(FuelImportRow::class, 'fuel_import_row_id');
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by_user_id');
    }
}
