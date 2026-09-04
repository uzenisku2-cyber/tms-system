<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FuelTransactionSettlementEligibilityEvaluation extends Model
{
    use HasUuids;

    protected $fillable = ['public_id', 'fuel_transaction_settlement_eligibility_id', 'revision', 'evaluation_version', 'status', 'result_code', 'fuel_card_settlement_policy_id', 'reconciliation_revision', 'settlement_target', 'target_organization_id', 'target_driver_id', 'discount_beneficiary', 'amount_basis', 'vat_mode', 'base_amount', 'currency', 'evidence', 'evaluated_by_user_id', 'evaluated_at'];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function eligibility(): BelongsTo
    {
        return $this->belongsTo(FuelTransactionSettlementEligibility::class, 'fuel_transaction_settlement_eligibility_id');
    }

    protected function casts(): array
    {
        return ['revision' => 'integer', 'reconciliation_revision' => 'integer', 'base_amount' => 'decimal:6', 'evidence' => 'array', 'evaluated_at' => 'immutable_datetime'];
    }
}
