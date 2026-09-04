<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FuelTransactionSettlementEligibility extends Model
{
    use HasUuids;

    public const STATUS_ELIGIBLE = 'eligible';

    public const STATUS_BLOCKED = 'blocked';

    protected $fillable = ['public_id', 'owner_organization_id', 'fuel_transaction_id', 'status', 'result_code', 'fuel_card_settlement_policy_id', 'reconciliation_revision', 'settlement_target', 'target_organization_id', 'target_driver_id', 'discount_beneficiary', 'amount_basis', 'vat_mode', 'base_amount', 'currency', 'revision', 'evaluated_at'];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FuelTransaction::class, 'fuel_transaction_id');
    }

    /** @return HasMany<FuelTransactionSettlementEligibilityEvaluation, $this> */
    public function evaluations(): HasMany
    {
        return $this->hasMany(FuelTransactionSettlementEligibilityEvaluation::class)->orderBy('revision');
    }

    protected function casts(): array
    {
        return ['reconciliation_revision' => 'integer', 'base_amount' => 'decimal:6', 'revision' => 'integer', 'evaluated_at' => 'immutable_datetime'];
    }
}
