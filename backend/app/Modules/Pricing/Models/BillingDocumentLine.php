<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BillingDocumentLine extends Model
{
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'billing_document_id',
        'financial_calculation_id',
        'description',
        'quantity',
        'unit_rate',
        'net_amount',
        'vat_amount',
        'gross_amount',
        'position',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_rate' => 'decimal:4',
            'net_amount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'created_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<BillingDocument, $this> */
    public function billingDocument(): BelongsTo
    {
        return $this->belongsTo(BillingDocument::class);
    }

    /** @return BelongsTo<FinancialCalculation, $this> */
    public function financialCalculation(): BelongsTo
    {
        return $this->belongsTo(FinancialCalculation::class);
    }
}
