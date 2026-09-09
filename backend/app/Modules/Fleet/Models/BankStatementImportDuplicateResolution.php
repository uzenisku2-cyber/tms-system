<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

final class BankStatementImportDuplicateResolution extends Model
{
    protected $fillable = ['public_id', 'organization_context_id', 'bank_statement_import_duplicate_candidate_id', 'idempotency_key', 'decision', 'reason', 'resolved_by_user_id', 'resolved_at'];

    protected function casts(): array
    {
        return ['resolved_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Bank statement duplicate resolutions are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Bank statement duplicate resolutions are append-only.'));
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function organizationContext(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_context_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(BankStatementImportDuplicateCandidate::class, 'bank_statement_import_duplicate_candidate_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}
