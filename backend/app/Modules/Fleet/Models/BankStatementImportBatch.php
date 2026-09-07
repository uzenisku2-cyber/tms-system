<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class BankStatementImportBatch extends Model
{
    protected $fillable = ['public_id', 'organization_context_id', 'idempotency_key', 'status', 'original_filename', 'file_sha256', 'source_type', 'parser_version', 'mapping_version', 'delimiter', 'encoding', 'mapping', 'source_row_count', 'accepted_row_count', 'duplicate_candidate_row_count', 'rejected_row_count', 'imported_by_user_id', 'completed_at'];

    protected function casts(): array
    {
        return ['mapping' => 'array', 'source_row_count' => 'integer', 'accepted_row_count' => 'integer', 'duplicate_candidate_row_count' => 'integer', 'rejected_row_count' => 'integer', 'completed_at' => 'immutable_datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function organizationContext(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_context_id');
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by_user_id');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(BankStatementImportRow::class, 'bank_statement_import_batch_id');
    }
}
