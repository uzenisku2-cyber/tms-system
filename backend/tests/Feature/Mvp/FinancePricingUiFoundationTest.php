<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class FinancePricingUiFoundationTest extends TestCase
{
    public function test_finance_ui_exposes_customers_and_four_financial_areas(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'data-finance-panel="customers"',
            'data-finance-panel="price-lists"',
            'data-finance-panel="billing"',
            'data-finance-panel="comparison"',
            'data-finance-panel="profitability"',
        ] as $marker) {
            self::assertStringContainsString(
                $marker,
                $source,
            );
        }

        self::assertStringContainsString(
            'Odběratelé',
            $source,
        );

        $hardcodedCustomerBrand =
            "Z\u{00E1}silkovna";

        self::assertStringNotContainsString(
            $hardcodedCustomerBrand,
            $source,
        );

        self::assertStringContainsString(
            'data-customer-index-endpoint="/api/v1/customers"',
            $source,
        );
    }

    public function test_billing_price_list_form_is_customer_specific_and_uses_canonical_codes(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        self::assertStringContainsString(
            'data-provider-managed-price-list-endpoint="/api/v1/customers/{relationship}/price-lists"',
            $source,
        );

        self::assertStringContainsString(
            'billing-price-list-customer',
            $source,
        );

        self::assertStringContainsString(
            'billing-price-list-valid-from',
            $source,
        );

        self::assertStringContainsString(
            'billing-price-list-valid-until',
            $source,
        );

        foreach ([
            'delivered_parcels',
            'redirected_parcels',
            'undelivered_parcels',
            'actual_km',
        ] as $pricingCode) {
            self::assertStringContainsString(
                'data-pricing-code="'.$pricingCode.'"',
                $source,
            );
        }
    }

    public function test_profitability_copy_uses_gross_margin_not_net_profit(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        self::assertStringContainsString(
            'Hrubá marže Kč',
            $source,
        );

        self::assertStringContainsString(
            'Marže %',
            $source,
        );

        self::assertStringContainsString(
            'čistý zisk',
            $source,
        );
    }

    public function test_finance_ui_loads_customers_through_existing_api_helper(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'S021-03E READ-ONLY FINANCE CUSTOMER LOADER',
            'const loadFinanceCustomers = async () => {',
            'const loadFinanceCustomerDetail = async (',
            'renderFinanceCustomerDetail',
            'customerIndexEndpoint',
            "if (page === 'finance') {",
            'loadFinanceCustomers();',
            '[data-customer-list]',
            '[data-billing-price-list-customer]',
            '[data-customer-detail]',
            'select.dataset.financeDetailBound',
            "!== '1'",
        ] as $marker) {
            self::assertStringContainsString(
                $marker,
                $source,
            );
        }

        self::assertStringContainsString(
            'await api(',
            $source,
        );

        self::assertStringContainsString(
            '`/api/v1/customers/${encodeURIComponent(relationshipId)}`',
            $source,
        );

        self::assertStringContainsString(
            'data-billing-price-list-save',
            $source,
        );
    }

    public function test_finance_ui_can_create_customer_through_existing_api_helper(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'data-customer-create-form',
            'data-customer-registration-number',
            'data-customer-valid-from',
            'data-customer-create-submit',
            'data-customer-create-message',
            'S021-03M BROWSER CUSTOMER CREATION',
            'const bindFinanceCustomerCreate = () => {',
            "method: 'POST'",
            'relationship_valid_from:',
            'await loadFinanceCustomers();',
            'await loadFinanceCustomerDetail(',
            'bindFinanceCustomerCreate();',
        ] as $marker) {
            self::assertStringContainsString(
                $marker,
                $source,
            );
        }

        self::assertStringContainsString(
            "'/api/v1/customers'",
            $source,
        );

        self::assertStringContainsString(
            'await api(',
            $source,
        );

        self::assertStringContainsString(
            'Billing-price-list creation remains a separate workflow.',
            $source,
        );
    }

    public function test_finance_ui_creates_complete_billing_price_list_draft_atomically(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'data-provider-managed-price-list-endpoint="/api/v1/customers/{relationship}/price-lists"',
            'data-billing-price-list-save',
            'data-billing-price-list-message',
            'S021-03N ATOMIC BILLING DRAFT',
            'const bindFinanceBillingPriceListCreate = () => {',
            "method: 'POST'",
            'canonicalCodes = [',
            "'delivered_parcels'",
            "'redirected_parcels'",
            "'undelivered_parcels'",
            "'actual_km'",
            'change_reason:',
            'items,',
            'kompletní draft v1 se čtyřmi sazbami',
            'bindFinanceBillingPriceListCreate();',
        ] as $marker) {
            self::assertStringContainsString(
                $marker,
                $source,
            );
        }

        self::assertStringContainsString(
            'await loadFinanceCustomerDetail(',
            $source,
        );

        self::assertStringNotContainsString(
            'Browser zápis zatím není aktivován',
            $source,
        );

        self::assertStringNotContainsString(
            '/versions/1',
            $source,
        );
    }

    public function test_finance_replacement_preserves_adjacent_renderers_and_template_initialization_order(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'const bank = () => `',
            'const imports = () => `',
            'const settings = () => `',
            'const templates = {',
        ] as $marker) {
            self::assertSame(
                1,
                substr_count($source, $marker),
                'Expected exactly one MVP renderer/template marker: '.$marker,
            );
        }

        $bankPosition = strpos($source, 'const bank = () => `');
        $importsPosition = strpos($source, 'const imports = () => `');
        $settingsPosition = strpos($source, 'const settings = () => `');
        $templatesPosition = strpos($source, 'const templates = {');

        self::assertIsInt($bankPosition);
        self::assertIsInt($importsPosition);
        self::assertIsInt($settingsPosition);
        self::assertIsInt($templatesPosition);

        self::assertTrue($bankPosition < $importsPosition);
        self::assertTrue($importsPosition < $settingsPosition);
        self::assertTrue($settingsPosition < $templatesPosition);

        self::assertStringContainsString(
            "Odb\u{011B}ratel\u{00E9}",
            $source,
        );

        self::assertStringContainsString(
            "Hrub\u{00E1} mar\u{017E}e K\u{010D}",
            $source,
        );
    }
}
