<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class FinancialSettlementBankMatchCandidateProposalContractTest extends TestCase
{
    public function test_proposal_is_exact_directional_scoped_explainable_and_non_executing(): void
    {
        $service = file_get_contents(__DIR__.'/../../../../app/Modules/Pricing/Services/FinancialSettlementBankMatchCandidateProposalService.php');
        self::assertIsString($service);
        foreach ([
            'owner_organization_id', 'organization_context_id', "->where('status', 'recorded')",
            'OUTPUT_CARRIER_PAYABLE', 'OUTPUT_CARRIER_RECEIVABLE', "=> 'debit'", "=> 'credit'",
            'settlementPaidMinor', 'outstandingMinor', 'remainingMinor', 'proposedAmountMinor',
            'minimum_score_basis_points', 'date_window_days', 'amount_partial',
            'amount_exact', 'direction_exact', 'currency_exact', 'variable_symbol_exact',
            'counterparty_account_exact', 'counterparty_name_exact', 'date_within_window',
            'candidate_fingerprint', 'lockForUpdate', 'candidate_proposed', 'replayed',
        ] as $marker) {
            self::assertStringContainsString($marker, $service);
        }
        self::assertStringContainsString("'payment_allocation_created' => false", $service);
        self::assertStringContainsString("'payment_marked' => false", $service);
        self::assertStringContainsString("'bank_matching_performed' => false", $service);
        foreach ([
            'SupplierFuelInvoiceBankPayment::query()', 'VehicleCostAllocationBankMatchingExecution::query()',
            "'payment_allocation_created' => true", "'payment_marked' => true",
            "'bank_matching_performed' => true", 'FinancialSettlementStatementEvent::query()->create',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $service);
        }
    }
}
