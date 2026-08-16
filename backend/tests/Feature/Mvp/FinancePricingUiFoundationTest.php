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
            'conditionalRules.length',
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

        self::assertIsInt(
            $billingFunctionStart = strpos(
                $source,
                'const bindFinanceBillingPriceListCreate = () => {',
            ),
        );

        self::assertIsInt(
            $billingFunctionEnd = strpos(
                $source,
                "\n            };",
                $billingFunctionStart,
            ),
        );

        $billingFunctionSource = substr(
            $source,
            $billingFunctionStart,
            $billingFunctionEnd
                + strlen("\n            };")
                - $billingFunctionStart,
        );

        self::assertStringNotContainsString(
            '/versions/1',
            $billingFunctionSource,
        );
    }

    public function test_billing_ui_administers_unlimited_conditional_surcharges(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'S023-04B UNLIMITED CONDITIONAL SURCHARGE UI',
            'data-conditional-rule-root',
            'data-conditional-rule-preset',
            'data-conditional-rule-add',
            'data-conditional-rule-list',
            'data-conditional-rule-code',
            'data-conditional-rule-name',
            'data-conditional-rule-metric-type',
            'data-conditional-rule-scope',
            'data-conditional-rule-reward-method',
            'data-conditional-numerator-source',
            'data-conditional-denominator-source',
            'data-conditional-band-add',
            'data-conditional-band-list',
            'data-conditional-band-adjustment',
            'const financeConditionalMetricSources = [',
            'const financeConditionalRulePresets = {',
            'const addFinanceConditionalRule = (',
            'const addFinanceConditionalBand = (',
            'const collectFinanceConditionalRules = (panel) => {',
            "addFinanceConditionalRule(panel, 'quality');",
            "addFinanceConditionalRule(panel, 'redirected');",
            'value="monthly_price_list"',
            "'customer_rejected_parcels'",
            "'not_delivered_parcels'",
            "'processed_parcels'",
            'metric_numerator_sources: numeratorSources,',
            'metric_denominator_sources: denominatorSources,',
            'reward_quantity_source:',
            'reward_target_item_code:',
            'conditional_rules:',
            'Odm&#237;tnuto z&#225;kazn&#237;kem',
        ] as $marker) {
            self::assertStringContainsString(
                $marker,
                $source,
            );
        }

        self::assertStringNotContainsString(
            'conditionalRules.slice(',
            $source,
        );

        self::assertStringNotContainsString(
            'conditionalRules.length >',
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

    public function test_driver_price_list_web_ui_creates_and_activates_first_tariff(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'S022-MVP-01 DRIVER PRICE LIST WEB UI',
            'data-driver-price-list-root',
            'data-driver-price-list-assignment',
            'data-driver-price-list-name',
            'data-driver-price-list-valid-from',
            'data-driver-price-list-valid-until',
            'data-driver-price-list-rate="delivered_parcels"',
            'data-driver-price-list-rate="redirected_parcels"',
            'data-driver-price-list-rate="undelivered_parcels"',
            'data-driver-price-list-rate="actual_km"',
            'data-driver-price-list-save',
            'data-driver-price-list-message',
            'data-driver-price-list-list',
            'const loadFinanceDriverAssignments = async () => {',
            'const loadFinanceDriverPriceLists = async () => {',
            'const bindFinanceDriverPriceListCreate = () => {',
            "'/api/v1/driver-price-lists'",
            '/versions/1/approve',
            '/versions/1/activate',
            'expected_lock_version:',
            'bindFinanceDriverPriceListCreate();',
            'loadFinanceDriverAssignments();',
            'loadFinanceDriverPriceLists();',
            'Uložit a aktivovat ceník',
        ] as $marker) {
            self::assertStringContainsString(
                $marker,
                $source,
            );
        }

        self::assertSame(
            4,
            substr_count(
                $source,
                'data-driver-price-list-rate="',
            ),
        );
    }
}
