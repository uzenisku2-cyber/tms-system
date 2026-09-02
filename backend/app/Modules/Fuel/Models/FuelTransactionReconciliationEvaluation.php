<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FuelTransactionReconciliationEvaluation extends Model
{
    use HasUuids;

    protected $fillable = ['public_id', 'fuel_transaction_reconciliation_id', 'revision', 'evaluation_version', 'result_code', 'effective_driver_id', 'driver_organization_assignment_id', 'candidate_count', 'matched_daily_report_id', 'evidence', 'evaluated_by_user_id', 'evaluated_at'];

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
    public function evaluatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by_user_id');
    }

    protected function casts(): array
    {
        return ['revision' => 'integer', 'candidate_count' => 'integer', 'evidence' => 'array', 'evaluated_at' => 'immutable_datetime'];
    }
}
