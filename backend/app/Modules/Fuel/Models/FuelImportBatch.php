<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FuelImportBatch extends Model
{
    protected $fillable = ['public_id', 'owner_organization_id', 'provider', 'status', 'original_filename', 'file_sha256', 'schema_fingerprint', 'period_start', 'period_end', 'source_row_count', 'accepted_row_count', 'duplicate_row_count', 'review_row_count', 'rejected_row_count', 'imported_by_user_id', 'completed_at'];

    protected $casts = ['period_start' => 'date', 'period_end' => 'date', 'source_row_count' => 'integer', 'accepted_row_count' => 'integer', 'duplicate_row_count' => 'integer', 'review_row_count' => 'integer', 'rejected_row_count' => 'integer', 'completed_at' => 'datetime'];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function rows(): HasMany
    {
        return $this->hasMany(FuelImportRow::class);
    }
}
