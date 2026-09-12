<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Fuel\Models\FuelTransactionSettlementApplication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SupplierFuelInvoiceRebillingCoverageLine extends Model
{
    public $timestamps = false;

    public const STATUS_COMPLETE = 'complete';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_MISSING = 'missing';

    public const STATUS_NEGATIVE_MARGIN = 'negative_margin';

    protected $fillable = [
        'public_id', 'coverage_id', 'supplier_fuel_invoice_transaction_allocation_id',
        'fuel_transaction_id', 'fuel_transaction_settlement_application_id',
        'financial_calculation_id', 'financial_settlement_statement_id',
        'output_billing_document_id', 'recipient_type', 'recipient_organization_id',
        'recipient_driver_id', 'comparison_basis', 'purchase_amount_minor',
        'rebilled_amount_minor', 'unrebilled_amount_minor', 'margin_minor', 'currency',
        'status', 'source_snapshot', 'position', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'purchase_amount_minor' => 'integer', 'rebilled_amount_minor' => 'integer',
            'unrebilled_amount_minor' => 'integer', 'margin_minor' => 'integer',
            'source_snapshot' => 'array', 'position' => 'integer', 'created_at' => 'immutable_datetime',
        ];
    }

    public function coverage(): BelongsTo
    {
        return $this->belongsTo(SupplierFuelInvoiceRebillingCoverage::class, 'coverage_id');
    }

    public function invoiceTransactionAllocation(): BelongsTo
    {
        return $this->belongsTo(SupplierFuelInvoiceTransactionAllocation::class, 'supplier_fuel_invoice_transaction_allocation_id');
    }

    public function fuelTransaction(): BelongsTo
    {
        return $this->belongsTo(FuelTransaction::class);
    }

    public function settlementApplication(): BelongsTo
    {
        return $this->belongsTo(FuelTransactionSettlementApplication::class, 'fuel_transaction_settlement_application_id');
    }
}
