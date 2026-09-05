<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

class VehicleFinancingProvisionContractTest extends TestCase
{
    public function test_provision_financing_and_installments_are_explicit_append_only_and_financially_separate(): void
    {
        $migration = file_get_contents(__DIR__.'/../../../../database/migrations/2026_09_06_120000_create_vehicle_financing_and_provision_foundation.php');
        $vehicle = file_get_contents(__DIR__.'/../../../../app/Modules/Fleet/Models/Vehicle.php');
        self::assertIsString($migration);
        self::assertIsString($vehicle);

        foreach (['own_vehicle', 'free_use', 'rental', 'operating_lease', 'finance_lease', 'purchase_installment', 'recipient_type', 'recipient_organization_id', 'recipient_user_id', 'billing_mode', 'invoice_required', 'deposit_offset', 'vat_mode', 'vehicle_installment_values_check'] as $marker) {
            self::assertStringContainsString($marker, $migration);
        }
        foreach (['provisionAgreements', 'financingAgreements'] as $marker) {
            self::assertStringContainsString($marker, $vehicle);
        }
        foreach (['VehicleProvisionAgreement.php', 'VehicleProvisionPrice.php', 'VehicleFinancingAgreement.php', 'VehicleInstallmentSchedule.php', 'VehicleInstallment.php'] as $model) {
            $source = file_get_contents(__DIR__.'/../../../../app/Modules/Fleet/Models/'.$model);
            self::assertIsString($source);
            self::assertStringContainsString('append-only', $source);
        }
        foreach (['financial_calculation_id', 'billing_document_id', 'bank_transaction_id', 'payment_id', 'tax_document_id', 'repair_fund_transaction_id'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $migration);
        }
    }

    public function test_manual_price_and_participant_roles_are_explicit_without_automatic_charge(): void
    {
        $migration = file_get_contents(__DIR__.'/../../../../database/migrations/2026_09_06_120000_create_vehicle_financing_and_provision_foundation.php');
        self::assertIsString($migration);

        foreach (['provider_type', 'provider_organization_id', 'provider_user_id', 'recipient_type', 'recipient_organization_id', 'recipient_user_id', 'amount', 'currency', 'billing_period', 'billing_mode', 'vat_mode', 'invoice_required', 'deposit_offset', 'informational_only', 'manual_review'] as $marker) {
            self::assertStringContainsString($marker, $migration);
        }
        foreach (['financial_calculation_id', 'billing_document_id', 'bank_transaction_id', 'payment_id', 'tax_document_id', 'repair_fund_transaction_id'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $migration);
        }
    }
}
