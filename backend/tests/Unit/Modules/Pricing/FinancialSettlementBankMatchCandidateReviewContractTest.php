<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class FinancialSettlementBankMatchCandidateReviewContractTest extends TestCase
{
    public function test_review_is_scoped_optimistic_idempotent_audited_and_non_executing(): void
    {
        $service = file_get_contents(__DIR__.'/../../../../app/Modules/Pricing/Services/FinancialSettlementBankMatchCandidateProposalService.php');
        $request = file_get_contents(__DIR__.'/../../../../app/Modules/Pricing/Requests/ReviewFinancialSettlementBankMatchCandidateRequest.php');
        $controller = file_get_contents(__DIR__.'/../../../../app/Modules/Pricing/Controllers/FinancialSettlementBankMatchCandidateController.php');
        $routes = file_get_contents(__DIR__.'/../../../../app/Modules/Pricing/Routes/api.php');
        self::assertIsString($service);
        self::assertIsString($request);
        self::assertIsString($controller);
        self::assertIsString($routes);
        foreach ([
            'STATUS_PROPOSED', 'expected_revision', 'review_command_fingerprint', 'lockForUpdate',
            'candidate_accepted', 'candidate_rejected', 'candidate_superseded',
            'reviewed_by_user_id', 'reviewed_at', 'review_reason', 'replayed',
        ] as $marker) {
            self::assertStringContainsString($marker, $service.$request);
        }
        self::assertStringContainsString('ReviewFinancialSettlementBankMatchCandidateRequest', $controller);
        self::assertStringContainsString('financial-settlement-statements/{financialSettlementStatement}/bank-match-candidates/{candidate}/review', $routes);
        self::assertStringContainsString("'payment_allocation_created' => false", $service);
        self::assertStringContainsString("'payment_marked' => false", $service);
        self::assertStringContainsString("'bank_matching_performed' => false", $service);
        foreach ([
            "'payment_allocation_created' => true", "'payment_marked' => true", "'bank_matching_performed' => true",
            'SupplierFuelInvoiceBankPayment::query()', 'VehicleCostAllocationBankMatchingExecution::query()',
            'FinancialSettlementStatementEvent::query()->create', 'BillingDocumentEvent::query()->create',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $service);
        }
    }
}
