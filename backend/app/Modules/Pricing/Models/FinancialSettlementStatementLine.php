<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

final class FinancialSettlementStatementLine extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    public const SOURCE_FINANCIAL_CALCULATION = 'financial_calculation';

    public const SOURCE_MUTUAL_CHARGE = 'financial_mutual_charge';

    public const EFFECT_EARNING = 'earning';

    public const EFFECT_DEDUCTION = 'deduction';

    /** @var list<string> */
    public const SOURCE_TYPES = [self::SOURCE_FINANCIAL_CALCULATION, self::SOURCE_MUTUAL_CHARGE];

    /** @var list<string> */
    public const EFFECTS = [self::EFFECT_EARNING, self::EFFECT_DEDUCTION];

    protected $fillable = [
        'public_id', 'financial_settlement_statement_id', 'position',
        'source_type', 'source_public_id', 'source_revision',
        'financial_calculation_id', 'financial_mutual_charge_id',
        'effect', 'description', 'amount_minor', 'currency',
        'source_snapshot', 'created_at',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementStatement::class, 'financial_settlement_statement_id');
    }

    public function financialCalculation(): BelongsTo
    {
        return $this->belongsTo(FinancialCalculation::class);
    }

    public function mutualCharge(): BelongsTo
    {
        return $this->belongsTo(FinancialMutualCharge::class, 'financial_mutual_charge_id');
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Financial settlement statement lines are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Financial settlement statement lines are append-only.'));
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer', 'source_revision' => 'integer',
            'amount_minor' => 'integer', 'source_snapshot' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }
}
