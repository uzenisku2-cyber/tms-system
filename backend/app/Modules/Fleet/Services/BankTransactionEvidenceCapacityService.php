<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Fleet\Models\VehicleCostAllocationBankMatchingExecution;
use App\Modules\Pricing\Models\FinancialSettlementBankPayment;
use App\Modules\Pricing\Models\SupplierFuelInvoiceBankPayment;

final class BankTransactionEvidenceCapacityService
{
    public function allocatedMinor(BankTransactionEvidence $evidence): int
    {
        $supplierFuelMinor = (int) SupplierFuelInvoiceBankPayment::query()
            ->where('bank_transaction_evidence_id', $evidence->id)
            ->where('status', SupplierFuelInvoiceBankPayment::STATUS_ACTIVE)
            ->sum('allocated_amount_minor');
        $settlementMinor = (int) FinancialSettlementBankPayment::query()
            ->where('bank_transaction_evidence_id', $evidence->id)
            ->where('status', FinancialSettlementBankPayment::STATUS_ACTIVE)
            ->sum('allocated_amount_minor');
        $vehicleCostMinor = 0;
        foreach (VehicleCostAllocationBankMatchingExecution::query()
            ->where('bank_transaction_evidence_id', $evidence->id)
            ->where('status', 'executed')
            ->pluck('matched_amount') as $amount) {
            $vehicleCostMinor += $this->minor((string) $amount);
        }

        return $supplierFuelMinor + $settlementMinor + $vehicleCostMinor;
    }

    public function remainingMinor(BankTransactionEvidence $evidence): int
    {
        return $this->minor((string) $evidence->amount) - $this->allocatedMinor($evidence);
    }

    public function minor(string $amount): int
    {
        if (preg_match('/^(\d+)\.(\d{2})$/', $amount, $parts) !== 1) {
            throw new \LogicException('Bank amount is not an exact two-decimal value.');
        }

        return ((int) $parts[1] * 100) + (int) $parts[2];
    }
}
