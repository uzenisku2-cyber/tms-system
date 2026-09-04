<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FuelTransactionSettlementApplicationEvent extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    public const TYPE_APPLIED = 'applied';

    public const TYPE_REVERSED = 'reversed';

    protected $fillable = ['public_id', 'fuel_transaction_settlement_application_id', 'revision', 'event_type', 'from_status', 'to_status', 'acted_by_user_id', 'reason', 'metadata', 'occurred_at', 'created_at'];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(FuelTransactionSettlementApplication::class, 'fuel_transaction_settlement_application_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by_user_id');
    }

    protected function casts(): array
    {
        return ['revision' => 'integer', 'metadata' => 'array', 'occurred_at' => 'immutable_datetime'];
    }
}
