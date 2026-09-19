<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

final class FinancialSettlementAccountingPostingCorrectionEvent extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['public_id', 'correction_id', 'event_type', 'payload', 'actor_user_id', 'occurred_at', 'revision'];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Financial settlement accounting posting correction events are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Financial settlement accounting posting correction events are append-only.'));
    }

    public function correction(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementAccountingPostingCorrection::class, 'correction_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    protected function casts(): array
    {
        return ['payload' => 'array', 'occurred_at' => 'immutable_datetime', 'revision' => 'integer'];
    }
}
