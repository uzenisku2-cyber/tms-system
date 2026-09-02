<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FuelTransactionReconciliationDecision extends Model
{
    use HasUuids;

    protected $fillable = ['public_id', 'fuel_transaction_reconciliation_id', 'revision', 'previous_status', 'new_status', 'decision_code', 'selected_daily_report_id', 'reason', 'decided_by_user_id', 'decided_at'];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    /** @return BelongsTo<FuelTransactionReconciliation, $this> */
    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(FuelTransactionReconciliation::class);
    }

    /** @return BelongsTo<User, $this> */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }

    protected function casts(): array
    {
        return ['revision' => 'integer', 'decided_at' => 'immutable_datetime'];
    }
}
