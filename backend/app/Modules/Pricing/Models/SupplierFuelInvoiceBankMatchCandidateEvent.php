<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

final class SupplierFuelInvoiceBankMatchCandidateEvent extends Model
{
    use HasUuids;

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'supplier_fuel_invoice_bank_match_candidate_id', 'revision',
        'event_type', 'idempotency_key', 'candidate_fingerprint', 'reason',
        'evidence', 'actor_user_id', 'occurred_at',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(SupplierFuelInvoiceBankMatchCandidate::class, 'supplier_fuel_invoice_bank_match_candidate_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Supplier fuel invoice bank match candidate events are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Supplier fuel invoice bank match candidate events are append-only.'));
    }

    protected function casts(): array
    {
        return ['revision' => 'integer', 'evidence' => 'array', 'occurred_at' => 'immutable_datetime'];
    }
}
