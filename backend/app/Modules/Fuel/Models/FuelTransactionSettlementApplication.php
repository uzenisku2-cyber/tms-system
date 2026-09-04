<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Pricing\Models\FinancialCalculation;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FuelTransactionSettlementApplication extends Model
{
    use HasUuids;

    public const STATUS_APPLIED = 'applied';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = ['public_id', 'owner_organization_id', 'fuel_transaction_id', 'fuel_transaction_settlement_eligibility_id', 'eligibility_revision', 'reconciliation_revision', 'fuel_card_settlement_policy_id', 'settlement_target', 'target_organization_id', 'target_driver_id', 'discount_beneficiary', 'amount_basis', 'vat_mode', 'applied_amount', 'currency', 'financial_calculation_id', 'status', 'revision', 'applied_by_user_id', 'applied_at', 'reversed_by_user_id', 'reversed_at', 'reversal_reason'];

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

    public function eligibility(): BelongsTo
    {
        return $this->belongsTo(FuelTransactionSettlementEligibility::class, 'fuel_transaction_settlement_eligibility_id');
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(FuelCardSettlementPolicy::class, 'fuel_card_settlement_policy_id');
    }

    public function targetOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'target_organization_id');
    }

    public function targetDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'target_driver_id');
    }

    public function financialCalculation(): BelongsTo
    {
        return $this->belongsTo(FinancialCalculation::class);
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by_user_id');
    }

    /** @return HasMany<FuelTransactionSettlementApplicationEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(FuelTransactionSettlementApplicationEvent::class)->orderBy('revision');
    }

    protected function casts(): array
    {
        return ['eligibility_revision' => 'integer', 'reconciliation_revision' => 'integer', 'applied_amount' => 'decimal:6', 'revision' => 'integer', 'applied_at' => 'immutable_datetime', 'reversed_at' => 'immutable_datetime'];
    }
}
